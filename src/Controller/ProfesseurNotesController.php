<?php

namespace App\Controller;

use App\Entity\ConfigurationBulletin;
use App\Entity\ConfigurationFicheCotation;
use App\Entity\Cours;
use App\Entity\Eleve;
use App\Entity\Evaluation;
use App\Entity\Note;
use App\Entity\Professeur;
use App\Entity\User;
use App\Repository\ConfigurationBulletinRepository;
use App\Repository\ConfigurationFicheCotationRepository;
use App\Repository\AutorisationCotationRepository;
use App\Repository\CoursRepository;
use App\Repository\EleveRepository;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CotationStructure;

#[Route('/professeur/notes', name: 'app_professeur_notes_')]
final class ProfesseurNotesController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'], priority: 10)]
    #[IsGranted('ROLE_PROFESSEUR')]
    public function index(
        Request $request,
        CoursRepository $coursRepository,
        EleveRepository $eleveRepository,
        ConfigurationFicheCotationRepository $ficheConfigurationRepository,
        ConfigurationBulletinRepository $configurationRepository,
        NoteRepository $noteRepository,
        CotationStructure $cotationStructure
    ): Response {
        $professeur = $this->professeurConnecte();
        $courses = $this->coursAttribues($coursRepository, $professeur);
        $schools = [];
        foreach ($courses as $cours) {
            $ecole = $cours->getClasse()?->getEcole();
            if ($ecole !== null) {
                $schools[$ecole->getId()] = $ecole;
            }
        }
        uasort($schools, static fn ($a, $b): int => strcasecmp($a->getNom(), $b->getNom()));

        $schoolId = $request->query->getInt('ecole') ?: null;
        if ($schoolId !== null && !isset($schools[$schoolId])) {
            $schoolId = null;
        }
        $schoolCourses = $schoolId === null
            ? []
            : array_values(array_filter(
                $courses,
                static fn (Cours $cours): bool => $cours->getClasse()?->getEcole()?->getId() === $schoolId
            ));

        $classes = [];
        foreach ($schoolCourses as $cours) {
            $classe = $cours->getClasse();
            if ($classe !== null) {
                $classes[$classe->getId()] = $classe;
            }
        }
        uasort($classes, static fn ($a, $b): int => strcasecmp($a->getNom(), $b->getNom()));

        $classId = $request->query->getInt('classe') ?: null;
        if ($classId !== null && !isset($classes[$classId])) {
            $classId = null;
        }
        $classCourses = $classId === null
            ? []
            : array_values(array_filter(
                $schoolCourses,
                static fn (Cours $cours): bool => $cours->getClasse()?->getId() === $classId
            ));

        $options = [];
        $hasUnoptionedCourses = false;
        foreach ($classCourses as $cours) {
            $option = $cours->getOption();
            if ($option === null) {
                $hasUnoptionedCourses = true;
            } else {
                $options[$option->getId()] = $option;
            }
        }
        uasort($options, static fn ($a, $b): int => strcasecmp($a->getNom(), $b->getNom()));

        $optionValue = $request->query->get('option');
        $optionId = is_scalar($optionValue) && ctype_digit((string) $optionValue)
            ? (int) $optionValue
            : null;
        if (
            $optionId !== null
            && (($optionId === 0 && !$hasUnoptionedCourses) || ($optionId > 0 && !isset($options[$optionId])))
        ) {
            $optionId = null;
        }

        $availableCourses = array_values(array_filter(
                $classCourses,
                static fn (Cours $cours): bool => $optionId === null
                    || ($optionId === 0
                        ? $cours->getOption() === null
                        : $cours->getOption()?->getId() === $optionId)
        ));
        usort($availableCourses, static fn (Cours $a, Cours $b): int => strcasecmp($a->getNom(), $b->getNom()));

        $courseId = $request->query->getInt('cours') ?: null;
        $selectedCourse = null;
        foreach ($availableCourses as $cours) {
            if ($cours->getId() === $courseId) {
                $selectedCourse = $cours;
                break;
            }
        }

        $selectedSchool = null;
        if ($schoolId !== null) {
            foreach ($schools as $school) {
                if ($school->getId() === $schoolId) {
                    $selectedSchool = $school;
                    break;
                }
            }
        }
        $ficheConfiguration = $selectedSchool === null
            ? null
            : $ficheConfigurationRepository->findOneBy(['ecole' => $selectedSchool]);
        $groups = $ficheConfiguration instanceof ConfigurationFicheCotation
            && $ficheConfiguration->getStructure() !== null
            ? $cotationStructure->groupes($ficheConfiguration->getStructure())
            : [];
        $students = $selectedCourse === null ? [] : $this->elevesPourCours($selectedCourse, $eleveRepository);
        $gradeGroups = $selectedCourse === null || $groups === []
            ? []
            : $this->groupesPourCours($selectedCourse, $professeur, $configurationRepository, $groups);
        $groupesManquants = count(array_filter(
            $gradeGroups,
            static fn (array $group): bool => $group['configuration_entity'] === null
                || in_array([], $group['evaluations'], true)
        )) > 0;
        $notes = $this->notesPourFiche($students, $gradeGroups, $noteRepository);
        [$gradesByPeriod, $groupTotals] = $this->cotesParPeriode($students, $gradeGroups, $notes);

        return $this->render('professeur/notes.html.twig', [
            'ecoles' => array_values($schools),
            'classes' => array_values($classes),
            'options' => array_values($options),
            'cours_sans_option' => $hasUnoptionedCourses,
            'cours' => $availableCourses,
            'ecole_selectionnee' => $schoolId,
            'classe_selectionnee' => $classId,
            'option_selectionnee' => $optionId,
            'cours_selectionne' => $selectedCourse,
            'eleves' => $students,
            'configuration_fiche_cotation' => $ficheConfiguration,
            'structure_fiche_cotation' => $ficheConfiguration?->getStructure(),
            'groupes_colonnes' => $groups,
            'groupes_cours' => $gradeGroups,
            'groupes_manquants' => $groupesManquants,
            'cotes_par_periode' => $gradesByPeriod,
            'totaux_groupes' => $groupTotals,
        ]);
    }

    #[Route('/filtres/{niveau}', name: 'filtres', methods: ['GET'])]
    #[IsGranted('ROLE_PROFESSEUR')]
    public function filtres(string $niveau, Request $request, CoursRepository $coursRepository): JsonResponse
    {
        $courses = $this->coursAttribues($coursRepository, $this->professeurConnecte());
        $schoolId = $request->query->getInt('ecole');
        $classId = $request->query->getInt('classe');

        if ($niveau === 'classes') {
            $schoolCourses = array_values(array_filter(
                $courses,
                static fn (Cours $cours): bool => $cours->getClasse()?->getEcole()?->getId() === $schoolId
            ));
            if ($schoolId < 1 || $schoolCourses === []) {
                throw $this->createNotFoundException('Aucun cours attribué dans cette école.');
            }

            $classes = [];
            foreach ($schoolCourses as $cours) {
                $classe = $cours->getClasse();
                if ($classe !== null) {
                    $classes[$classe->getId()] = ['id' => $classe->getId(), 'nom' => $classe->getNom()];
                }
            }
            usort($classes, static fn (array $a, array $b): int => strcasecmp($a['nom'], $b['nom']));

            return $this->json(['items' => array_values($classes)]);
        }

        $classCourses = array_values(array_filter(
            $courses,
            static fn (Cours $cours): bool => $cours->getClasse()?->getId() === $classId
                && $cours->getClasse()?->getEcole()?->getId() === $schoolId
        ));
        if ($schoolId < 1 || $classId < 1 || $classCourses === []) {
            throw $this->createNotFoundException('Cette classe ne fait pas partie de vos cours.');
        }

        if ($niveau === 'options') {
            $options = [];
            foreach ($classCourses as $cours) {
                $option = $cours->getOption();
                if ($option !== null) {
                    $options[$option->getId()] = ['id' => $option->getId(), 'nom' => $option->getNom()];
                } else {
                    $options[0] = ['id' => 0, 'nom' => 'Cours sans option'];
                }
            }
            usort($options, static fn (array $a, array $b): int => strcasecmp($a['nom'], $b['nom']));

            return $this->json(['items' => array_values($options)]);
        }

        if ($niveau === 'cours') {
            $optionId = null;
            if ($request->query->has('option')) {
                $optionValue = $request->query->get('option');
                if (!is_scalar($optionValue) || !ctype_digit((string) $optionValue)) {
                    throw $this->createNotFoundException('Cette option ne fait pas partie de vos cours.');
                }
                $optionId = (int) $optionValue;
            }
            $matchingCourses = array_values(array_filter(
                $classCourses,
                static fn (Cours $cours): bool => $optionId === null
                    || ($optionId === 0
                        ? $cours->getOption() === null
                        : $cours->getOption()?->getId() === $optionId)
            ));
            if ($matchingCourses === []) {
                throw $this->createNotFoundException(
                    $optionId === null
                        ? 'Aucun cours attribué pour cette classe.'
                        : 'Cette option ne fait pas partie de vos cours.'
                );
            }

            $items = array_map(
                static fn (Cours $cours): array => [
                    'id' => $cours->getId(),
                    'nom' => $cours->getNom() . ($cours->getOption() !== null ? ' — ' . $cours->getOption()->getNom() : ''),
                ],
                $matchingCourses
            );
            usort($items, static fn (array $a, array $b): int => strcasecmp($a['nom'], $b['nom']));

            return $this->json(['items' => $items]);
        }

        throw $this->createNotFoundException('Filtre inconnu.');
    }

    #[Route('/cours/{id}', name: 'cours', methods: ['GET', 'POST'])]
    public function cours(
        int $id,
        Request $request,
        CoursRepository $coursRepository,
        ConfigurationBulletinRepository $configurationRepository,
        ConfigurationFicheCotationRepository $ficheConfigurationRepository,
        AutorisationCotationRepository $autorisationRepository,
        EleveRepository $eleveRepository,
        NoteRepository $noteRepository,
        CotationStructure $cotationStructure,
        EntityManagerInterface $entityManager
    ): Response {
        $isAdministrator = $this->isGranted('ROLE_ADMIN');
        if (!$isAdministrator && !$this->isGranted('ROLE_PROFESSEUR')) {
            throw $this->createAccessDeniedException();
        }
        $professeur = $isAdministrator ? null : $this->professeurConnecte();
        $cours = $isAdministrator
            ? $coursRepository->find($id)
            : $coursRepository->findOneBy(['id' => $id, 'professeur' => $professeur]);
        if ($isAdministrator && $cours instanceof Cours) {
            $professeur = $cours->getProfesseur();
        }
        if (
            !$cours instanceof Cours
            || (!$isAdministrator && !$professeur instanceof Professeur)
            || $cours->isDeleted()
            || $cours->getClasse() === null
            || $cours->getClasse()->isDeleted()
            || $cours->getClasse()->getEcole() === null
        ) {
            throw $this->createNotFoundException();
        }
        if ($isAdministrator && $request->isMethod('POST')) {
            throw $this->createAccessDeniedException('La direction consulte les cotes en lecture seule.');
        }

        $ficheConfiguration = $ficheConfigurationRepository->findOneBy([
            'ecole' => $cours->getClasse()->getEcole(),
        ]);
        if (!$ficheConfiguration instanceof ConfigurationFicheCotation || $ficheConfiguration->getStructure() === null) {
            $this->addFlash('warning', 'L’administration doit configurer le modèle de cotation de cette école avant la saisie.');

            return $isAdministrator
                ? $this->redirectToRoute('app_validation_notes_index')
                : $this->redirectToRoute('app_professeur_notes_index', [
                    'ecole' => $cours->getClasse()->getEcole()->getId(),
                    'classe' => $cours->getClasse()->getId(),
                    'option' => $cours->getOption()?->getId() ?? 0,
                    'cours' => $cours->getId(),
                ]);
        }

        $modelGroups = $cotationStructure->groupes($ficheConfiguration->getStructure());
        $periodAuthorizations = $autorisationRepository->autorisationsPourStructure($ficheConfiguration->getStructure());
        $groups = $this->groupesPourCours($cours, $professeur, $configurationRepository, $modelGroups);
        $eleves = $this->elevesPourCours($cours, $eleveRepository);
        $semestres = [];
        $evaluations = [];
        foreach ($groups as $group) {
            $configuration = $group['configuration_entity'];
            if (!$configuration instanceof ConfigurationBulletin) {
                continue;
            }

            $periodEvaluations = [];
            foreach ($group['evaluations'] as $evaluationsForPeriod) {
                foreach ($evaluationsForPeriod as $evaluation) {
                    $periodEvaluations[] = $evaluation;
                    $evaluations[$evaluation->getId()] = $evaluation;
                }
            }
            $semestres[$configuration->getId()] = [
                'configuration' => $configuration,
                'evaluations' => $periodEvaluations,
                'evaluations_par_periode' => $group['evaluations'],
                'periodes' => $group['periodes'],
            ];
        }

        $studentIds = [];
        $elevesParId = [];
        foreach ($eleves as $eleve) {
            $studentIds[$eleve->getId()] = true;
            $elevesParId[$eleve->getId()] = $eleve;
        }
        $notes = [];
        if ($evaluations !== [] && $eleves !== []) {
            foreach ($noteRepository->findForEvaluationsAndEleves(array_values($evaluations), $eleves) as $note) {
                $studentId = $note->getEleve()?->getId();
                $evaluationId = $note->getEvaluation()?->getId();
                if ($studentId !== null && $evaluationId !== null && isset($studentIds[$studentId])) {
                    $notes[$evaluationId][$studentId] = $note;
                }
            }
        }

        $mode = $isAdministrator
            ? 'consultation'
            : ($request->query->get('mode') === 'modifier' ? 'modifier' : 'noter');
        $evaluationsSaisissables = [];
        $periodesAvecDoublons = [];
        $anneeScolaire = null;
        foreach ($groups as $group) {
            $anneeScolaire ??= $group['configuration_entity']?->getAnneeScolaire();
            foreach ($group['periodes'] as $period) {
                $periodEvaluations = $group['evaluations'][$period['nom']] ?? [];
                if (count($periodEvaluations) > 1) {
                    $periodesAvecDoublons[$period['nom']] = true;
                }
                if (
                    ($periodAuthorizations[$period['nom']] ?? false)
                    && count($periodEvaluations) === 1
                    && ($periodEvaluations[0]->getBareme() ?? 0) > 0
                ) {
                    $evaluationsSaisissables[$period['nom']] = $periodEvaluations[0];
                }
            }
        }

        $peutSaisir = false;
        foreach ($evaluationsSaisissables as $evaluation) {
            foreach ($eleves as $eleve) {
                $note = $notes[$evaluation->getId()][$eleve->getId()] ?? null;
                if (
                    ($mode === 'noter' && !($note instanceof Note))
                    || ($mode === 'modifier' && $note instanceof Note && $note->getStatut() === 'brouillon')
                ) {
                    $peutSaisir = true;
                    break 2;
                }
            }
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'notes_' . $cours->getId(),
                (string) $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException('Le jeton de sécurité est invalide.');
            }

            $action = (string) $request->request->get('action');
            if (!in_array($action, ['enregistrer', 'soumettre'], true)) {
                throw $this->createAccessDeniedException();
            }

            $submittedGrades = $request->request->all()['notes'] ?? [];
            if (!is_array($submittedGrades)) {
                throw $this->createAccessDeniedException('Les notes envoyées ne correspondent pas à cette cotation.');
            }
            foreach ($submittedGrades as $evaluationId => $studentGrades) {
                $evaluation = $evaluations[$evaluationId] ?? null;
                if (
                    !($evaluation instanceof Evaluation)
                    || $evaluation->getCours()?->getId() !== $cours->getId()
                    || !is_array($studentGrades)
                ) {
                    throw $this->createAccessDeniedException('Une cotation envoyée ne correspond pas à ce cours.');
                }
                $periodName = trim((string) $evaluation->getNom());
                $saisissable = $evaluationsSaisissables[$periodName] ?? null;
                if (
                    !($periodAuthorizations[$periodName] ?? false)
                    || !($saisissable instanceof Evaluation)
                    || $saisissable->getId() !== $evaluation->getId()
                ) {
                    throw $this->createAccessDeniedException('Cette période n’est pas ouverte à la saisie.');
                }
                foreach ($studentGrades as $studentId => $grade) {
                    if (!isset($studentIds[(int) $studentId]) || !is_scalar($grade)) {
                        throw $this->createAccessDeniedException();
                    }
                }
            }

            $changes = [];
            $changedGrades = 0;
            foreach ($submittedGrades as $evaluationId => $studentGrades) {
                $evaluation = $evaluations[$evaluationId];
                foreach ($studentGrades as $studentId => $submittedGrade) {
                    $studentId = (int) $studentId;
                    $rawGrade = trim((string) $submittedGrade);
                    $note = $notes[$evaluation->getId()][$studentId] ?? null;
                    if ($note instanceof Note && in_array($note->getStatut(), ['soumise', 'validee'], true)) {
                        if ($rawGrade !== (string) $note->getCote()) {
                            throw $this->createAccessDeniedException('Une note soumise ou validée ne peut plus être modifiée.');
                        }
                        continue;
                    }
                    if ($mode === 'noter' && $note instanceof Note) {
                        if ($rawGrade !== (string) $note->getCote()) {
                            throw $this->createAccessDeniedException('Utilisez « Modifier » pour corriger une cote existante.');
                        }
                        continue;
                    }
                    if ($mode === 'modifier' && !($note instanceof Note) && $rawGrade !== '') {
                        throw $this->createAccessDeniedException('Utilisez « Ajouter » pour saisir une nouvelle cote.');
                    }

                    if ($rawGrade === '') {
                        if ($note instanceof Note) {
                            $changes[] = ['action' => 'supprimer', 'note' => $note];
                        }
                        continue;
                    }
                    $eleve = $elevesParId[$studentId];
                    if (
                        !preg_match('/^\d+$/D', $rawGrade)
                        || (int) $rawGrade > ($evaluation->getBareme() ?? 0)
                    ) {
                        $this->addFlash('danger', sprintf(
                            'La cote de %s doit être un nombre entier compris entre 0 et %d.',
                            $eleve?->getNom() . ' ' . $eleve?->getPrenom(),
                            $evaluation->getBareme() ?? 0
                        ));

                        return $this->redirectToRoute('app_professeur_notes_cours', [
                            'id' => $cours->getId(),
                            'mode' => $mode,
                        ]);
                    }

                    if ($note instanceof Note && $rawGrade === (string) $note->getCote() && $action === 'enregistrer') {
                        continue;
                    }
                    ++$changedGrades;
                    $changes[] = [
                        'action' => 'enregistrer',
                        'note' => $note ?? (new Note())->setEleve($eleve)->setEvaluation($evaluation),
                        'cote' => $rawGrade,
                    ];
                }
            }

            if ($action === 'soumettre' && $changedGrades === 0) {
                $this->addFlash('warning', 'Aucune nouvelle cote à soumettre.');

                return $this->redirectToRoute('app_professeur_notes_cours', [
                    'id' => $cours->getId(),
                    'mode' => $mode,
                ]);
            }
            if ($action === 'enregistrer' && $changes === []) {
                $this->addFlash('warning', 'Aucune cote n’a été modifiée.');

                return $this->redirectToRoute('app_professeur_notes_cours', [
                    'id' => $cours->getId(),
                    'mode' => $mode,
                ]);
            }

            foreach ($changes as $change) {
                if ($change['action'] === 'supprimer') {
                    $entityManager->remove($change['note']);
                    continue;
                }

                $note = $change['note'];
                $note->setCote($change['cote']);
                if ($action === 'soumettre') {
                    $note->setStatut('soumise');
                    $note->setDateSoumission(new \DateTimeImmutable());
                }
                if ($note->getId() === null) {
                    $entityManager->persist($note);
                }
            }
            $entityManager->flush();
            $this->addFlash(
                'success',
                $action === 'soumettre' ? 'Les cotes ont été soumises à l’administration.' : 'Les cotes ont été enregistrées.'
            );

            return $this->redirectToRoute('app_professeur_notes_index', [
                'ecole' => $cours->getClasse()->getEcole()->getId(),
                'classe' => $cours->getClasse()->getId(),
                'option' => $cours->getOption()?->getId() ?? 0,
                'cours' => $cours->getId(),
            ]);
        }

        [$gradesByPeriod, $groupTotals] = $this->cotesParPeriode($eleves, $groups, $notes);

        return $this->render('professeur/notes_cours.html.twig', [
            'cours' => $cours,
            'eleves' => $eleves,
            'semestres' => $semestres,
            'groupes_colonnes' => $modelGroups,
            'groupes_cours' => $groups,
            'annee_scolaire' => $anneeScolaire,
            'cotes_par_periode' => $gradesByPeriod,
            'totaux_groupes' => $groupTotals,
            'structure_fiche_cotation' => $ficheConfiguration->getStructure(),
            'autorisations_periodes' => $periodAuthorizations,
            'evaluations_saisissables' => $evaluationsSaisissables,
            'periodes_avec_doublons' => $periodesAvecDoublons,
            'peut_saisir' => $peutSaisir,
            'notes' => $notes,
            'nombre_periodes_autorisees' => count(array_filter(
                $periodAuthorizations,
                static fn (bool $authorized): bool => $authorized
            )),
            'mode' => $mode,
        ]);
    }

    /**
     * @param array<int, array{nom: string, configuration: string, periodes: array<int, array{libelle: string, nom: string}>, total: string}> $modelGroups
     * @return array<int, array{nom: string, configuration: string, periodes: array<int, array{libelle: string, nom: string}>, total: string, configuration_entity: ?ConfigurationBulletin, evaluations: array<string, Evaluation[]>}>
     */
    private function groupesPourCours(
        Cours $cours,
        ?Professeur $professeur,
        ConfigurationBulletinRepository $configurationRepository,
        array $modelGroups
    ): array {
        $configurations = $configurationRepository->findBy(['classe' => $cours->getClasse()]);
        $groups = [];

        foreach ($modelGroups as $group) {
            $selectedConfiguration = null;
            foreach ($configurations as $configuration) {
                if (
                    $this->cleConfiguration($configuration->getNom()) !== $this->cleConfiguration($group['configuration'])
                    || !$this->configurationPourCours($configuration, $cours, $professeur)
                ) {
                    continue;
                }

                if ($selectedConfiguration === null) {
                    $selectedConfiguration = $configuration;
                    continue;
                }

                $existingIsSpecific = $selectedConfiguration->getOptions() !== null;
                $candidateIsSpecific = $configuration->getOptions() !== null;
                if ($existingIsSpecific === $candidateIsSpecific) {
                    throw new \LogicException(sprintf(
                        'Plusieurs configurations existent pour « %s » et le cours « %s ». ',
                        $group['configuration'],
                        $cours->getNom()
                    ));
                }
                if ($candidateIsSpecific) {
                    $selectedConfiguration = $configuration;
                }
            }

            $evaluationsByPeriod = [];
            foreach ($group['periodes'] as $period) {
                $evaluationsByPeriod[$period['nom']] = [];
            }
            if ($selectedConfiguration !== null) {
                foreach ($selectedConfiguration->getEvaluations() as $evaluation) {
                    if ($evaluation->getCours()?->getId() !== $cours->getId()) {
                        continue;
                    }
                    foreach (array_keys($evaluationsByPeriod) as $periodName) {
                        if (strcasecmp(trim((string) $evaluation->getNom()), $periodName) === 0) {
                            $evaluationsByPeriod[$periodName][] = $evaluation;
                            break;
                        }
                    }
                }
            }

            $groups[] = $group + [
                'configuration_entity' => $selectedConfiguration,
                'evaluations' => $evaluationsByPeriod,
            ];
        }

        return $groups;
    }

    /**
     * @param Eleve[] $eleves
     * @param array<int, array{evaluations: array<string, Evaluation[]>}> $groups
     * @return array<int, array<int, array<int, Note>>>
     */
    private function notesPourFiche(array $eleves, array $groups, NoteRepository $noteRepository): array
    {
        $evaluations = [];
        foreach ($groups as $group) {
            foreach ($group['evaluations'] as $periodEvaluations) {
                foreach ($periodEvaluations as $evaluation) {
                    $evaluations[$evaluation->getId()] = $evaluation;
                }
            }
        }

        $studentsById = [];
        foreach ($eleves as $eleve) {
            $studentsById[$eleve->getId()] = true;
        }

        $notes = [];
        if ($evaluations !== [] && $eleves !== []) {
            foreach ($noteRepository->findForEvaluationsAndEleves(array_values($evaluations), $eleves) as $note) {
                $studentId = $note->getEleve()?->getId();
                $evaluationId = $note->getEvaluation()?->getId();
                if ($studentId !== null && $evaluationId !== null && isset($studentsById[$studentId])) {
                    $notes[$evaluationId][$studentId] = $note;
                }
            }
        }

        return $notes;
    }

    /**
     * @param Eleve[] $eleves
     * @param array<int, array{configuration: string, periodes: array<int, array{nom: string}>, evaluations: array<string, Evaluation[]>}> $groups
     * @param array<int, array<int, array<int, Note>>> $notes
     * @return array{0: array<int, array<string, array{cote: int, bareme: int}>>, 1: array<int, array<string, int>>}
     */
    private function cotesParPeriode(array $eleves, array $groups, array $notes): array
    {
        $gradesByPeriod = [];
        $groupTotals = [];
        foreach ($eleves as $eleve) {
            $studentId = $eleve->getId();
            foreach ($groups as $group) {
                $groupTotal = 0;
                $groupHasGrades = false;
                foreach ($group['periodes'] as $period) {
                    $periodGrade = 0;
                    $periodMaximum = 0;
                    $hasGrade = false;
                    foreach ($group['evaluations'][$period['nom']] ?? [] as $evaluation) {
                        $note = $notes[$evaluation->getId()][$studentId] ?? null;
                        if (!$note instanceof Note) {
                            continue;
                        }
                        $periodGrade += (int) $note->getCote();
                        $periodMaximum += $evaluation->getBareme() ?? 0;
                        $hasGrade = true;
                    }
                    if ($hasGrade) {
                        $gradesByPeriod[$studentId][$period['nom']] = [
                            'cote' => $periodGrade,
                            'bareme' => $periodMaximum,
                        ];
                        $groupTotal += $periodGrade;
                        $groupHasGrades = true;
                    }
                }
                if ($groupHasGrades) {
                    $groupTotals[$studentId][$group['configuration']] = $groupTotal;
                }
            }
        }

        return [$gradesByPeriod, $groupTotals];
    }

    /**
     * @return Eleve[]
     */
    private function elevesPourCours(Cours $cours, EleveRepository $eleveRepository): array
    {
        $criteria = ['classe' => $cours->getClasse(), 'statut' => true, 'deleted' => false];
        if ($cours->getOption() !== null) {
            $criteria['option'] = $cours->getOption();
        }

        return $eleveRepository->findBy($criteria, ['nom' => 'ASC', 'postnom' => 'ASC', 'prenom' => 'ASC']);
    }

    private function professeurConnecte(): Professeur
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getProfesseur() instanceof Professeur) {
            throw $this->createAccessDeniedException('Aucun profil professeur n’est associé à ce compte.');
        }

        return $user->getProfesseur();
    }

    /**
     * @return Cours[]
     */
    private function coursAttribues(CoursRepository $coursRepository, Professeur $professeur): array
    {
        return array_values(array_filter(
            $coursRepository->findBy(['professeur' => $professeur]),
            static fn (Cours $cours): bool => !$cours->isDeleted()
                && $cours->getClasse() !== null
                && !$cours->getClasse()->isDeleted()
                && $cours->getClasse()->getEcole() !== null
        ));
    }

    private function configurationPourCours(
        ConfigurationBulletin $configuration,
        Cours $cours,
        ?Professeur $professeur
    ): bool {
        $classe = $cours->getClasse();
        $ecole = $classe?->getEcole();
        if (
            $classe === null
            || $ecole === null
            || $configuration->getEcole()?->getId() !== $ecole->getId()
            || $configuration->getClasse()?->getId() !== $classe->getId()
            || (
                $configuration->getOptions() !== null
                && $configuration->getOptions()?->getId() !== $cours->getOption()?->getId()
            )
        ) {
            return false;
        }

        $anneeProfesseur = $professeur?->getAnneeScolaire();

        return $anneeProfesseur === null
            || $anneeProfesseur->getId() === $configuration->getAnneeScolaire()?->getId();
    }

    private function cleConfiguration(?string $nom): ?string
    {
        if (
            $nom === null
            || preg_match('/^(Semestre|Trimestre)\s+(I{1,3}|[1-3])$/iu', trim($nom), $matches) !== 1
        ) {
            return null;
        }

        $type = strtolower($matches[1]);
        $number = match (strtoupper($matches[2])) {
            'I', '1' => 1,
            'II', '2' => 2,
            'III', '3' => 3,
        };
        if (($type === 'semestre' && $number > 2) || ($type === 'trimestre' && $number > 3)) {
            return null;
        }

        return $type . '-' . $number;
    }

}
