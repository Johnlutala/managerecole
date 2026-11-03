<?php

namespace App\Service;

use App\Entity\AnneeScolaire;
use App\Entity\BulletinAnnuel;
use App\Entity\Classe;
use App\Entity\ConfigurationBulletin;
use App\Entity\Cours;
use App\Entity\Ecole;
use App\Entity\Eleve;
use App\Entity\Evaluation;
use App\Entity\Option;
use App\Entity\Section;
use App\Repository\BulletinAnnuelRepository;
use App\Repository\AutorisationCotationRepository;
use App\Repository\ConfigurationFicheCotationRepository;
use App\Repository\ConfigurationBulletinRepository;
use App\Repository\CoursRepository;
use App\Repository\EleveRepository;
use App\Repository\NoteRepository;
use App\Service\CotationStructure;
use Doctrine\ORM\EntityManagerInterface;

final class BulletinAnnuelCalculator
{
    public function __construct(
        private readonly ConfigurationBulletinRepository $configurations,
        private readonly CoursRepository $courses,
        private readonly EleveRepository $students,
        private readonly NoteRepository $notes,
        private readonly BulletinAnnuelRepository $bulletins,
        private readonly ConfigurationFicheCotationRepository $ficheConfigurations,
        private readonly AutorisationCotationRepository $authorizations,
        private readonly CotationStructure $cotationStructure,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array{calcules: int, bloques: array<int, array{classe: string, option: string, raisons: string[]}>, sans_periode_autorisee?: bool}
     */
    public function calculate(
        Ecole $school,
        AnneeScolaire $schoolYear,
        ?Section $section = null,
        ?Classe $selectedClass = null,
        ?Option $selectedOption = null
    ): array {
        return $this->entityManager->wrapInTransaction(function () use (
            $school,
            $schoolYear,
            $section,
            $selectedClass,
            $selectedOption
        ): array {
            $ficheConfiguration = $this->ficheConfigurations->findOneBy(['ecole' => $school]);
            $structure = $ficheConfiguration?->getStructure();
            $authorizedPeriods = [];
            $allPeriods = [];
            if ($structure !== null) {
                $enabledPeriods = $this->authorizations->autorisationsPourStructure($structure);
                $periodOrder = 0;
                foreach ($this->cotationStructure->groupes($structure) as $periodGroup) {
                    foreach ($periodGroup['periodes'] as $period) {
                        ++$periodOrder;
                        $descriptor = [
                            'libelle' => $period['libelle'],
                            'semestre' => $periodGroup['nom'],
                            'ordre' => $periodOrder,
                        ];
                        $allPeriods[$periodGroup['configuration']][$period['nom']] = $descriptor;
                        if ($enabledPeriods[$period['nom']] ?? false) {
                            $authorizedPeriods[$periodGroup['configuration']][$period['nom']] = $descriptor;
                        }
                    }
                }
            }

            $configurations = array_values(array_filter(
                $this->configurations->findBy([
                    'ecole' => $school,
                    'anneeScolaire' => $schoolYear,
                ]),
                static fn (ConfigurationBulletin $configuration): bool =>
                    $configuration->getClasse() !== null
                    && !$configuration->getClasse()->isDeleted()
                    && ($section === null
                        || $configuration->getClasse()->getSection()?->getId() === $section->getId())
                    && ($selectedClass === null
                        || $configuration->getClasse()->getId() === $selectedClass->getId())
            ));

            $configurationsByClass = [];
            foreach ($configurations as $configuration) {
                $classId = $configuration->getClasse()?->getId();
                if ($classId === null) {
                    continue;
                }
                $configurationsByClass[$classId][] = $configuration;
            }

            $calculated = 0;
            $blocked = [];
            if ($authorizedPeriods === []) {
                return [
                    'calcules' => 0,
                    'bloques' => [],
                    'sans_periode_autorisee' => true,
                ];
            }

            foreach ($configurationsByClass as $classConfigurations) {
                $class = $classConfigurations[0]->getClasse();
                if (!$class instanceof Classe) {
                    continue;
                }

                $configurationMap = $this->configurationMap($classConfigurations);
                $courses = array_values(array_filter(
                    $this->courses->findBy(['classe' => $class], ['nom' => 'ASC']),
                    static fn (Cours $course): bool => !$course->isDeleted()
                ));
                $students = array_values(array_filter(
                    $this->students->findBy([
                        'classe' => $class,
                        'statut' => true,
                        'deleted' => false,
                    ], ['nom' => 'ASC', 'postnom' => 'ASC', 'prenom' => 'ASC']),
                    static fn (Eleve $student): bool => $selectedOption === null
                        || $student->getOption()?->getId() === $selectedOption->getId()
                ));

                $studentsByOption = [];
                foreach ($students as $student) {
                    $option = $student->getOption();
                    $optionKey = $option?->getId() === null ? 'sans-option' : (string) $option->getId();
                    $studentsByOption[$optionKey]['option'] = $option;
                    $studentsByOption[$optionKey]['students'][] = $student;
                }

                foreach ($studentsByOption as $group) {
                    $option = $group['option'];
                    $groupStudents = $group['students'];
                    $coursesForOption = array_values(array_filter(
                        $courses,
                        static fn (Cours $course): bool => $course->getOption()?->getId() === null
                            || $course->getOption()?->getId() === $option?->getId()
                    ));
                    $allCourseEvaluations = $this->evaluationsForCourses(
                        $coursesForOption,
                        $configurationMap,
                        $allPeriods
                    );
                    $reasons = [];
                    foreach ($allCourseEvaluations as $courseId => $entries) {
                        $periodLabels = [];
                        foreach ($entries as $entry) {
                            if (isset($periodLabels[$entry['periode']])) {
                                $courseName = $this->courses->find($courseId)?->getNom() ?? 'inconnu';
                                $reasons[] = sprintf(
                                    'Plusieurs évaluations correspondent à la même période pour le cours « %s » (%s).',
                                    $courseName,
                                    $entry['periodLabel']
                                );
                            }
                            $periodLabels[$entry['periode']] = true;
                        }
                    }
                    if ($groupStudents === []) {
                        $reasons[] = 'Aucun élève actif inscrit dans cette option.';
                    }
                    $existingBulletins = $this->bulletins->findBy([
                        'classe' => $class,
                        'anneeScolaire' => $schoolYear,
                        'option' => $option,
                    ]);
                    $existingByStudent = [];
                    foreach ($existingBulletins as $existingBulletin) {
                        $studentId = $existingBulletin->getEleve()?->getId();
                        if ($studentId !== null) {
                            $existingByStudent[$studentId] = $existingBulletin;
                        }
                    }

                    $periodsForGroup = $this->periodsForGroup($allCourseEvaluations);
                    $courseNamesById = [];
                    foreach ($coursesForOption as $course) {
                        $courseNamesById[$course->getId()] = (string) $course->getNom();
                    }
                    $periodToCalculate = $this->nextPeriodToCalculate(
                        $periodsForGroup,
                        $authorizedPeriods,
                        $allCourseEvaluations,
                        $courseNamesById,
                        $groupStudents,
                        $existingByStudent
                    );
                    if ($periodToCalculate === null) {
                        $pendingPeriod = $this->firstIncompletePeriod(
                            $periodsForGroup,
                            $allCourseEvaluations,
                            $courseNamesById,
                            $groupStudents,
                            $existingByStudent
                        );
                        if ($pendingPeriod !== null) {
                            $reasons[] = sprintf(
                                'La période « %s » doit être autorisée et calculée avant de passer à la suite.',
                                $pendingPeriod['periodLabel']
                            );
                        } else {
                            $reasons[] = 'Aucune évaluation configurée ne correspond à une période autorisée pour cette classe-option.';
                        }
                    }

                    $courseEvaluations = $periodToCalculate === null
                        ? []
                        : $this->evaluationsForSinglePeriod($allCourseEvaluations, $periodToCalculate['periode']);
                    $allEvaluations = [];
                    foreach ($courseEvaluations as $entries) {
                        foreach ($entries as $entry) {
                            $allEvaluations[$entry['evaluation']->getId()] = $entry['evaluation'];
                        }
                    }

                    $notesByKey = [];
                    if ($allEvaluations !== [] && $groupStudents !== []) {
                        foreach ($this->notes->findForEvaluationsAndEleves(
                            array_values($allEvaluations),
                            $groupStudents
                        ) as $note) {
                            $evaluationId = $note->getEvaluation()?->getId();
                            $studentId = $note->getEleve()?->getId();
                            if ($evaluationId !== null && $studentId !== null) {
                                $notesByKey[$evaluationId][$studentId] = $note;
                            }
                        }
                    }

                    $results = [];
                    foreach ($groupStudents as $student) {
                        $studentId = $student->getId();
                        $details = [];
                        $totalCotes = 0;
                        $totalBaremes = 0;
                        foreach ($coursesForOption as $course) {
                            $courseGrades = [];
                            $courseCotes = 0;
                            $courseBaremes = 0;
                            foreach ($courseEvaluations[$course->getId()] ?? [] as $entry) {
                                $evaluation = $entry['evaluation'];
                                $note = $notesByKey[$evaluation->getId()][$studentId] ?? null;
                                $validated = $note !== null && $note->getStatut() === 'validee';
                                $cote = $validated ? (int) $note->getCote() : 1;
                                $bareme = $evaluation->getBareme() ?? 0;
                                if ($bareme < 1) {
                                    $reasons[] = sprintf('Barème invalide pour le cours « %s ».', $course->getNom());
                                    continue;
                                }
                                $courseCotes += $cote;
                                $courseBaremes += $bareme;
                                $totalCotes += $cote;
                                $totalBaremes += $bareme;
                                $courseGrades[] = [
                                    'periode' => $entry['periode'],
                                    'libelle' => $entry['periodLabel'],
                                    'semestre' => $entry['semester'],
                                    'cote' => $cote,
                                    'bareme' => $bareme,
                                    'provisoire' => !$validated,
                                    'autorisee' => true,
                                ];
                            }

                            $details[] = [
                                'cours_id' => $course->getId(),
                                'cours' => $course->getNom() ?? '',
                                'domaine' => $course->getDomaine(),
                                'evaluations' => $courseGrades,
                                'total_cotes' => $courseCotes,
                                'total_baremes' => $courseBaremes,
                                'pourcentage' => $courseBaremes > 0
                                    ? number_format($courseCotes * 100 / $courseBaremes, 2, '.', '')
                                    : '0.00',
                            ];
                        }

                        if ($totalBaremes > 0) {
                            $percentage = number_format($totalCotes * 100 / $totalBaremes, 2, '.', '');
                            $results[] = [
                                'student' => $student,
                                'details' => $details,
                                'total_cotes' => $totalCotes,
                                'total_baremes' => $totalBaremes,
                                'pourcentage' => $percentage,
                                'decision' => (float) $percentage >= 50 ? 'admis' : 'ajourne',
                            ];
                        }
                    }

                    if ($reasons !== []) {
                        $blocked[] = [
                            'classe' => $class->getNom() ?? '',
                            'option' => $option?->getNom() ?? 'Sans option',
                            'raisons' => array_slice(array_values(array_unique($reasons)), 0, 8),
                        ];
                        continue;
                    }

                    foreach ($results as $result) {
                        $student = $result['student'];
                        $bulletin = $existingByStudent[$student->getId()] ?? new BulletinAnnuel();
                        unset($existingByStudent[$student->getId()]);
                        $details = $this->mergeDetails(
                            $bulletin->getDetails(),
                            $result['details'],
                            $periodToCalculate['ordre']
                        );
                        [$totalCotes, $totalBaremes] = $this->totalsForDetails($details);
                        if ($totalBaremes < 1) {
                            continue;
                        }
                        $percentage = number_format($totalCotes * 100 / $totalBaremes, 2, '.', '');
                        $bulletin
                            ->setEleve($student)
                            ->setClasse($class)
                            ->setEcole($school)
                            ->setAnneeScolaire($schoolYear)
                            ->setOption($option)
                            ->setDetails($details)
                            ->setTotalCotes($totalCotes)
                            ->setTotalBaremes($totalBaremes)
                            ->setPourcentage($percentage)
                            ->setDecision((float) $percentage >= 50 ? 'admis' : 'ajourne')
                            ->setCalculeLe(new \DateTimeImmutable());
                        $this->entityManager->persist($bulletin);
                        ++$calculated;
                    }
                }
            }

            return ['calcules' => $calculated, 'bloques' => $blocked];
        });
    }

    /**
     * @param ConfigurationBulletin[] $configurations
     * @return array<string, array<string, ConfigurationBulletin>>
     */
    private function configurationMap(array $configurations): array
    {
        $map = [];
        foreach ($configurations as $configuration) {
            $periodName = trim((string) $configuration->getNom());
            $optionId = $configuration->getOptions()?->getId();
            $optionKey = $optionId === null ? 'global' : (string) $optionId;
            if (isset($map[$periodName][$optionKey])) {
                throw new \LogicException(sprintf(
                    'Plusieurs configurations existent pour « %s » et l’option « %s ».',
                    $periodName,
                    $configuration->getOptions()?->getNom() ?? 'commune'
                ));
            }
            $map[$periodName][$optionKey] = $configuration;
        }

        return $map;
    }

    /**
     * @param Cours[] $courses
     * @param array<string, array<string, ConfigurationBulletin>> $configurationMap
     * @param array<string, array<string, array{libelle: string, semestre: string, ordre: int}>> $authorizedPeriods
     * @return array<int, array<int, array{evaluation: Evaluation, libelle: string, periode: string, periodLabel: string, semester: string, ordre: int, configuration: string}>>
     */
    private function evaluationsForCourses(array $courses, array $configurationMap, array $authorizedPeriods): array
    {
        $result = [];
        foreach ($courses as $course) {
            $courseId = $course->getId();
            if ($courseId === null) {
                continue;
            }
            $courseOptionId = $course->getOption()?->getId();
            foreach ($configurationMap as $periodName => $options) {
                $configuration = $courseOptionId === null
                    ? ($options['global'] ?? null)
                    : ($options[(string) $courseOptionId] ?? $options['global'] ?? null);
                if (!$configuration instanceof ConfigurationBulletin) {
                    continue;
                }
                foreach ($configuration->getEvaluations() as $evaluation) {
                    if ($evaluation->getCours()?->getId() !== $courseId) {
                        continue;
                    }
                    $evaluationName = trim((string) $evaluation->getNom());
                    $period = $authorizedPeriods[$periodName][$evaluationName] ?? null;
                    if ($period === null) {
                        continue;
                    }
                    $result[$courseId][] = [
                        'evaluation' => $evaluation,
                        'libelle' => $periodName . ' — ' . $evaluationName,
                        'periode' => $evaluationName,
                        'periodLabel' => $period['libelle'],
                        'semester' => $period['semestre'],
                        'ordre' => $period['ordre'],
                        'configuration' => $periodName,
                    ];
                }
            }
            if (isset($result[$courseId])) {
                usort(
                    $result[$courseId],
                    static fn (array $first, array $second): int =>
                        strnatcasecmp($first['libelle'], $second['libelle'])
                );
            }
        }

        return $result;
    }

    /**
     * @param array<int, array<int, array{evaluation: Evaluation, libelle: string, periode: string, periodLabel: string, semester: string, ordre: int}>> $courseEvaluations
     * @return array<string, array{periode: string, periodLabel: string, ordre: int, configuration: string}>
     */
    private function periodsForGroup(array $courseEvaluations): array
    {
        $periods = [];
        foreach ($courseEvaluations as $entries) {
            foreach ($entries as $entry) {
                $periods[$entry['periode']] = [
                    'periode' => $entry['periode'],
                    'periodLabel' => $entry['periodLabel'],
                    'ordre' => $entry['ordre'],
                    'configuration' => $entry['configuration'],
                ];
            }
        }
        uasort($periods, static fn (array $first, array $second): int => $first['ordre'] <=> $second['ordre']);

        return $periods;
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $courseEvaluations
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function evaluationsForSinglePeriod(array $courseEvaluations, string $period): array
    {
        $result = [];
        foreach ($courseEvaluations as $courseId => $entries) {
            foreach ($entries as $entry) {
                if ($entry['periode'] === $period) {
                    $result[$courseId][] = $entry;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, array{periode: string, periodLabel: string, ordre: int, configuration: string}> $periods
     * @param array<string, array<string, array{libelle: string, semestre: string, ordre: int}>> $authorizedPeriods
     * @param array<int, array<int, array<string, mixed>>> $courseEvaluations
     * @param array<int, string> $courseNames
     * @param Eleve[] $students
     * @param array<int, BulletinAnnuel> $bulletinsByStudent
     * @return array{periode: string, periodLabel: string, ordre: int, configuration: string}|null
     */
    private function nextPeriodToCalculate(
        array $periods,
        array $authorizedPeriods,
        array $courseEvaluations,
        array $courseNames,
        array $students,
        array $bulletinsByStudent
    ): ?array {
        $pending = $this->firstIncompletePeriod($periods, $courseEvaluations, $courseNames, $students, $bulletinsByStudent);
        if ($pending !== null) {
            return isset($authorizedPeriods[$pending['configuration']][$pending['periode']])
                ? $pending
                : null;
        }

        $authorizedCompleted = array_filter(
            $periods,
            static fn (array $period): bool => isset(
                $authorizedPeriods[$period['configuration']][$period['periode']]
            )
        );
        if ($authorizedCompleted === []) {
            return null;
        }
        uasort($authorizedCompleted, static fn (array $first, array $second): int => $first['ordre'] <=> $second['ordre']);

        return end($authorizedCompleted) ?: null;
    }

    /**
     * @param array<string, array{periode: string, periodLabel: string, ordre: int, configuration: string}> $periods
     * @param array<int, array<int, array<string, mixed>>> $courseEvaluations
     * @param array<int, string> $courseNames
     * @param Eleve[] $students
     * @param array<int, BulletinAnnuel> $bulletinsByStudent
     * @return array{periode: string, periodLabel: string, ordre: int, configuration: string}|null
     */
    private function firstIncompletePeriod(
        array $periods,
        array $courseEvaluations,
        array $courseNames,
        array $students,
        array $bulletinsByStudent
    ): ?array {
        foreach ($periods as $period) {
            if (!$this->periodIsComplete(
                $period['periode'],
                $courseEvaluations,
                $courseNames,
                $students,
                $bulletinsByStudent
            )) {
                return $period;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $courseEvaluations
     * @param array<int, string> $courseNames
     * @param Eleve[] $students
     * @param array<int, BulletinAnnuel> $bulletinsByStudent
     */
    private function periodIsComplete(
        string $period,
        array $courseEvaluations,
        array $courseNames,
        array $students,
        array $bulletinsByStudent
    ): bool {
        foreach ($students as $student) {
            $bulletin = $bulletinsByStudent[$student->getId()] ?? null;
            if (!$bulletin instanceof BulletinAnnuel) {
                return false;
            }
            $courseDetails = [];
            foreach ($bulletin->getDetails() as $detail) {
                $courseId = $detail['cours_id'] ?? null;
                if ($courseId !== null) {
                    $courseDetails[(string) $courseId] = $detail;
                } else {
                    $courseDetails[(string) ($detail['cours'] ?? '')] = $detail;
                }
            }

            foreach ($courseEvaluations as $courseId => $entries) {
                $required = array_values(array_filter(
                    $entries,
                    static fn (array $entry): bool => $entry['periode'] === $period
                ));
                if ($required === []) {
                    continue;
                }
                $detail = $courseDetails[(string) $courseId]
                    ?? $courseDetails[$courseNames[$courseId] ?? '']
                    ?? null;
                if ($detail === null) {
                    return false;
                }
                $savedEvaluations = [];
                foreach ($detail['evaluations'] ?? [] as $evaluation) {
                    $savedEvaluations[$this->normalizedPeriodKey((string) ($evaluation['periode'] ?? ''))][] = $evaluation;
                }
                foreach ($required as $_entry) {
                    $saved = $savedEvaluations[$period] ?? [];
                    if ($saved === []) {
                        return false;
                    }
                    $calculated = false;
                    foreach ($saved as $savedEvaluation) {
                        if (($savedEvaluation['autorisee'] ?? false) === true) {
                            $calculated = true;
                            break;
                        }
                    }
                    if (!$calculated) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function normalizedPeriodKey(string $period): string
    {
        if (preg_match('/(?<period>(?:Période|Examen)\s*\d+)\s*$/iu', $period, $matches)) {
            return trim($matches['period']);
        }

        return trim($period);
    }

    /**
     * @param array<int, array<string, mixed>> $existingDetails
     * @param array<int, array<string, mixed>> $currentDetails
     * @param int $latestAuthorizedOrder
     * @return array<int, array<string, mixed>>
     */
    private function mergeDetails(array $existingDetails, array $currentDetails, int $latestAuthorizedOrder): array
    {
        $merged = [];
        $currentCourseIdsByName = [];
        foreach ($currentDetails as $detail) {
            if (isset($detail['cours_id'])) {
                $currentCourseIdsByName[(string) ($detail['cours'] ?? '')] = (string) $detail['cours_id'];
            }
        }
        foreach ($existingDetails as $detail) {
            $storedEvaluations = [];
            foreach ($detail['evaluations'] ?? [] as $evaluation) {
                $evaluation = $this->normalizeStoredEvaluation($evaluation, $latestAuthorizedOrder);
                if ($evaluation['autorisee'] ?? false) {
                    $storedEvaluations[$evaluation['periode']] = $evaluation;
                }
            }
            $detail['evaluations'] = array_values($storedEvaluations);
            $courseName = (string) ($detail['cours'] ?? '');
            $key = (string) ($detail['cours_id'] ?? $currentCourseIdsByName[$courseName] ?? $courseName);
            $merged[$key] = $detail;
        }

        foreach ($currentDetails as $detail) {
            $key = (string) ($detail['cours_id'] ?? $detail['cours'] ?? '');
            $existing = $merged[$key] ?? null;
            $evaluations = [];
            foreach ($existing['evaluations'] ?? [] as $evaluation) {
                $evaluations[$evaluation['periode']] = $evaluation;
            }
            foreach ($detail['evaluations'] as $evaluation) {
                $evaluations[$evaluation['periode']] = $evaluation;
            }
            $detail['evaluations'] = array_values($evaluations);
            [$detail['total_cotes'], $detail['total_baremes']] = $this->totalsForEvaluations($detail['evaluations']);
            $detail['pourcentage'] = $detail['total_baremes'] > 0
                ? number_format($detail['total_cotes'] * 100 / $detail['total_baremes'], 2, '.', '')
                : '0.00';
            $merged[$key] = $detail;
        }

        return array_values($merged);
    }

    /**
     * @param array<string, mixed> $evaluation
     * @return array<string, mixed>
     */
    private function normalizeStoredEvaluation(array $evaluation, int $latestAuthorizedOrder): array
    {
        $period = trim((string) ($evaluation['periode'] ?? ''));
        if (preg_match('/(?<period>(?:Période|Examen)\s*\d+)\s*$/iu', $period, $matches)) {
            $prefix = trim(
                substr($period, 0, strlen($period) - strlen($matches[0])),
                " \t\n\r\0\x0B—–-?"
            );
            if ($prefix !== '' && !isset($evaluation['semestre'])) {
                $evaluation['semestre'] = $this->normalizeSemesterLabel($prefix);
            } elseif (isset($evaluation['semestre'])) {
                $evaluation['semestre'] = $this->normalizeSemesterLabel($evaluation['semestre']);
            }
            $period = trim($matches['period']);
            $evaluation['libelle'] ??= $period;
            $evaluation['periode'] = $period;
        }
        if (!array_key_exists('autorisee', $evaluation)) {
            $evaluation['autorisee'] =
                $this->periodOrder((string) $evaluation['periode']) <= $latestAuthorizedOrder;
        }

        return $evaluation;
    }

    private function normalizeSemesterLabel(string $semester): string
    {
        return match (mb_strtolower(trim($semester))) {
            'semestre i', '1er semestre' => '1ER SEMESTRE',
            'semestre ii', 'second semestre', '2ème semestre', '2eme semestre' => 'SECOND SEMESTRE',
            'trimestre i', '1er trimestre' => '1ER TRIMESTRE',
            'trimestre ii', '2ème trimestre', '2eme trimestre' => '2ÈME TRIMESTRE',
            'trimestre iii', '3ème trimestre', '3eme trimestre' => '3ÈME TRIMESTRE',
            default => trim($semester),
        };
    }

    private function periodOrder(string $period): int
    {
        if (preg_match('/Période\s*(\d+)/iu', $period, $matches)) {
            $number = (int) $matches[1];

            return $number + intdiv(max(0, $number - 1), 2);
        }
        if (preg_match('/Examen\s*(\d+)/iu', $period, $matches)) {
            return (int) $matches[1] * 3;
        }

        return PHP_INT_MAX;
    }

    /**
     * @param array<int, array<string, mixed>> $details
     * @return array{0: int, 1: int}
     */
    private function totalsForDetails(array $details): array
    {
        $totalCotes = 0;
        $totalBaremes = 0;
        foreach ($details as $detail) {
            [$cotes, $baremes] = $this->totalsForEvaluations($detail['evaluations'] ?? []);
            $totalCotes += $cotes;
            $totalBaremes += $baremes;
        }

        return [$totalCotes, $totalBaremes];
    }

    /**
     * @param array<int, array<string, mixed>> $evaluations
     * @return array{0: int, 1: int}
     */
    private function totalsForEvaluations(array $evaluations): array
    {
        $cotes = 0;
        $baremes = 0;
        foreach ($evaluations as $evaluation) {
            $cotes += (int) ($evaluation['cote'] ?? 0);
            $baremes += (int) ($evaluation['bareme'] ?? 0);
        }

        return [$cotes, $baremes];
    }
}
