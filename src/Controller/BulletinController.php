<?php

namespace App\Controller;

use App\Entity\AnneeScolaire;
use App\Entity\BulletinAnnuel;
use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Option;
use App\Entity\Section;
use App\Repository\AnneeScolaireRepository;
use App\Repository\BulletinAnnuelRepository;
use App\Repository\ClasseRepository;
use App\Repository\EcoleRepository;
use App\Repository\EleveRepository;
use App\Repository\NoteRepository;
use App\Repository\OptionRepository;
use App\Repository\SectionRepository;
use App\Service\BulletinAnnuelCalculator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/administration/bulletins', name: 'app_bulletin_')]
#[IsGranted('ROLE_ADMIN')]
final class BulletinController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        EcoleRepository $ecoles,
        SectionRepository $sections,
        ClasseRepository $classes,
        OptionRepository $options,
        AnneeScolaireRepository $annees,
        BulletinAnnuelRepository $bulletins,
        NoteRepository $notes,
        EleveRepository $students,
        BulletinAnnuelCalculator $calculator
    ): Response {
        $filters = $this->resolveFilters($request, $ecoles, $sections, $classes, $options, $annees);
        $validatedGrades = [];
        if ($filters['annee'] instanceof AnneeScolaire) {
            foreach ($notes->findValidatedEvaluationSummaries(
                $filters['annee'],
                $filters['ecole'],
                $filters['section'],
                $filters['classe'],
                $filters['option']
            ) as $summary) {
                $course = $summary['evaluation']->getCours();
                $class = $course?->getClasse();
                if ($course === null || $class === null) {
                    continue;
                }
                $expectedOption = $course->getOption()
                    ?? $summary['evaluation']->getConfigurationBulletin()?->getOptions()
                    ?? $filters['option'];
                $validatedGrades[] = [
                    ...$summary,
                    'attendues' => $students->countActiveForClasseAndOption($class, $expectedOption),
                ];
            }
        }
        $results = $bulletins->findForFilters(
            $filters['ecole'],
            $filters['annee'],
            $filters['section'],
            $filters['classe'],
            $filters['option']
        );
        $results = $this->keepCalculatedPeriodsOnly($results);
        $groups = [];
        foreach ($results as $bulletin) {
            $class = $bulletin->getClasse();
            $option = $bulletin->getOption();
            if ($class === null || $bulletin->getEcole()?->isDeleted()) {
                continue;
            }
            $key = $class->getId() . ':' . ($option?->getId() ?? 'none');
            $groups[$key] ??= [
                'ecole' => $bulletin->getEcole(),
                'classe' => $class,
                'option' => $option,
                'annee' => $bulletin->getAnneeScolaire(),
                'bulletins' => [],
                'total_pourcentages' => 0.0,
            ];
            $groups[$key]['bulletins'][] = $bulletin;
            $groups[$key]['total_pourcentages'] += (float) $bulletin->getPourcentage();
        }
        foreach ($groups as &$group) {
            $count = count($group['bulletins']);
            $group['eleves'] = $count;
            $group['moyenne'] = $count > 0
                ? number_format($group['total_pourcentages'] / $count, 2, ',', ' ')
                : '0,00';
        }
        unset($group);

        return $this->render('bulletin_annuel/index.html.twig', [
            ...$filters,
            'ecoles' => array_values(array_filter(
                $ecoles->findBy([], ['nom' => 'ASC']),
                static fn (Ecole $school): bool => !$school->isDeleted()
            )),
            'cotes_validees' => $validatedGrades,
            'groupes_resultats' => array_values($groups),
            'periodes' => $filters['ecole'] instanceof Ecole
                ? $calculator->periodsForSchool($filters['ecole'])
                : [],
        ]);
    }

    #[Route('/calculer', name: 'calculer', methods: ['POST'])]
    public function calculate(
        Request $request,
        EcoleRepository $ecoles,
        SectionRepository $sections,
        ClasseRepository $classes,
        OptionRepository $options,
        AnneeScolaireRepository $annees,
        BulletinAnnuelCalculator $calculator
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('calculer_bulletins_annuels', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Le formulaire de calcul est invalide.');
        }

        $filters = $this->resolveFilters($request, $ecoles, $sections, $classes, $options, $annees, true);
        foreach (['section' => 'section', 'classe' => 'classe', 'option' => 'option'] as $inputName => $filterName) {
            $submittedValue = $request->request->get($inputName);
            if (
                $submittedValue !== null
                && $submittedValue !== ''
                && (
                    !is_scalar($submittedValue)
                    || !ctype_digit((string) $submittedValue)
                    || ((int) $submittedValue > 0 && $filters[$filterName] === null)
                )
            ) {
                $this->addFlash('warning', 'Un filtre de classe ou d’option ne correspond pas à l’école sélectionnée.');

                return $this->redirectToRoute('app_bulletin_index', $this->filterRouteParameters($filters));
            }
        }
        if (!$filters['ecole'] instanceof Ecole || !$filters['annee'] instanceof AnneeScolaire) {
            $this->addFlash('warning', 'Choisissez une école et une année scolaire avant de lancer le calcul.');

            return $this->redirectToRoute('app_bulletin_index');
        }

        $requestedPeriod = $request->request->get('periode');
        if (!is_string($requestedPeriod) || trim($requestedPeriod) === '') {
            $this->addFlash('warning', 'Cochez la période à calculer.');

            return $this->redirectToRoute('app_bulletin_index', $this->filterRouteParameters($filters));
        }
        $periodIsAuthorized = false;
        foreach ($calculator->periodsForSchool($filters['ecole']) as $period) {
            if ($period['nom'] === $requestedPeriod && $period['autorisee']) {
                $periodIsAuthorized = true;
                break;
            }
        }
        if (!$periodIsAuthorized) {
            $this->addFlash('warning', 'La période sélectionnée n’existe pas pour cette école ou n’est pas autorisée.');

            return $this->redirectToRoute('app_bulletin_index', $this->filterRouteParameters($filters));
        }

        $result = $calculator->calculate(
            $filters['ecole'],
            $filters['annee'],
            $filters['section'],
            $filters['classe'],
            $filters['option'],
            $requestedPeriod
        );

        if ($result['calcules'] > 0) {
            $this->addFlash(
                'success',
                sprintf('%d bulletin(s) mis à jour pour la période autorisée.', $result['calcules'])
            );
        }
        if ($result['bloques'] !== []) {
            $blockedSummary = array_map(
                static fn (array $blocked): string => sprintf(
                    '%s — %s : %s',
                    $blocked['classe'],
                    $blocked['option'],
                    implode(' ', array_slice($blocked['raisons'], 0, 3))
                ),
                array_slice($result['bloques'], 0, 5)
            );
            $remainingGroups = count($result['bloques']) - count($blockedSummary);
            if ($remainingGroups > 0) {
                $blockedSummary[] = sprintf('… et %d autre(s) classe(s)/option(s).', $remainingGroups);
            }
            $this->addFlash(
                'warning',
                sprintf(
                    '%d classe(s)/option(s) non calculée(s) : la période attendue doit être autorisée et calculée avant de passer à la suivante, ou la configuration comporte un problème : %s',
                    count($result['bloques']),
                    implode(' ', $blockedSummary)
                )
            );
        }
        if ($result['sans_periode_autorisee'] ?? false) {
            $this->addFlash(
                'info',
                'Aucune période n’est autorisée pour la structure de cotation de cette école. Les bulletins existants ont été conservés.'
            );
        } elseif ($result['calcules'] === 0 && $result['bloques'] === []) {
            $this->addFlash('info', 'Aucune classe avec des cotations configurées ne correspond à ces filtres.');
        }

        return $this->redirectToRoute('app_bulletin_index', $this->filterRouteParameters($filters));
    }

    #[Route('/classe/{id}', name: 'classe', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showClass(
        Classe $classe,
        Request $request,
        AnneeScolaireRepository $annees,
        OptionRepository $options,
        BulletinAnnuelRepository $bulletins
    ): Response {
        [$schoolYear, $option] = $this->classReportFilters($classe, $request, $annees, $options);
        $results = $bulletins->findForClassAndYear($classe, $schoolYear, $option);
        $results = $this->keepCalculatedPeriodsOnly($results);
        if ($results === []) {
            $this->addFlash('info', 'Aucun résultat calculé pour cette classe, cette option et cette année scolaire.');

            return $this->redirectToRoute('app_bulletin_index', [
                'ecole' => $classe->getEcole()?->getId(),
                'classe' => $classe->getId(),
                'annee' => $schoolYear->getId(),
            ]);
        }

        return $this->render('bulletin_annuel/classe.html.twig', [
            'classe' => $classe,
            'option' => $option,
            'annee' => $schoolYear,
            'bulletins' => $results,
        ]);
    }

    #[Route('/eleve/{id}', name: 'eleve', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showStudent(BulletinAnnuel $bulletin, BulletinAnnuelRepository $bulletins): Response
    {
        $class = $bulletin->getClasse();
        $schoolYear = $bulletin->getAnneeScolaire();
        if (!$class instanceof Classe || !$schoolYear instanceof AnneeScolaire) {
            throw $this->createNotFoundException('Le bulletin annuel est incomplet.');
        }
        $classResults = $bulletins->findForClassAndYear(
            $class,
            $schoolYear,
            $bulletin->getOption()
        );
        $classResults = $this->keepCalculatedPeriodsOnly($classResults);
        if ($bulletin->getTotalBaremes() < 1) {
            $this->addFlash('info', 'Aucune période autorisée n’a encore été calculée pour cet élève.');

            return $this->redirectToRoute('app_bulletin_index', [
                'ecole' => $class->getEcole()?->getId(),
                'classe' => $class->getId(),
                'annee' => $schoolYear->getId(),
            ]);
        }
        $position = 1;
        foreach ($classResults as $index => $classResult) {
            if ($classResult->getId() === $bulletin->getId()) {
                $position = $index + 1;
                break;
            }
        }

        return $this->render('bulletin_annuel/eleve.html.twig', [
            'bulletin' => $bulletin,
            'position' => $position,
            'effectif' => count($classResults),
            'periode_headers' => $this->periodHeaders([$bulletin]),
        ]);
    }

    #[Route('/classe/{id}/pdf', name: 'pdf_classe', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadClassPdf(
        Classe $classe,
        Request $request,
        AnneeScolaireRepository $annees,
        OptionRepository $options,
        BulletinAnnuelRepository $bulletins
    ): Response {
        [$schoolYear, $option] = $this->classReportFilters($classe, $request, $annees, $options);
        $results = $bulletins->findForClassAndYear($classe, $schoolYear, $option);
        $results = $this->keepCalculatedPeriodsOnly($results);
        if ($results === []) {
            throw $this->createNotFoundException('Aucun bulletin calculé à télécharger pour ce filtre.');
        }

        $pdfOptions = new Options();
        $pdfOptions->set('isRemoteEnabled', false);
        $pdfOptions->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($this->renderView('bulletin_annuel/pdf.html.twig', [
            'bulletins' => $results,
            'periode_headers' => $this->periodHeaders($results),
        ]), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf(
            'bulletins-%s-%s.pdf',
            preg_replace('/[^A-Za-z0-9-]+/', '-', (string) $classe->getNom()),
            preg_replace('/[^A-Za-z0-9-]+/', '-', (string) $schoolYear->getLibelle())
        );
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
        );

        return $response;
    }

    /**
     * @return array{
     *     ecole: ?Ecole,
     *     annees: AnneeScolaire[],
     *     annee: ?AnneeScolaire,
     *     sections: Section[],
     *     section: ?Section,
     *     classes: Classe[],
     *     classe: ?Classe,
     *     options: Option[],
     *     option: ?Option
     * }
     */
    private function resolveFilters(
        Request $request,
        EcoleRepository $ecoles,
        SectionRepository $sections,
        ClasseRepository $classes,
        OptionRepository $options,
        AnneeScolaireRepository $annees,
        bool $fromPost = false
    ): array {
        $input = $fromPost ? $request->request : $request->query;
        $schoolId = $input->getInt('ecole');
        $school = $schoolId > 0 ? $ecoles->find($schoolId) : null;
        if (!$school instanceof Ecole || $school->isDeleted()) {
            $school = null;
        }

        $schoolYears = $school instanceof Ecole
            ? $annees->findBy(['ecole' => $school], ['dateDebut' => 'DESC'])
            : [];
        $yearId = $input->getInt('annee');
        $schoolYear = $yearId > 0 ? $annees->find($yearId) : ($schoolYears[0] ?? null);
        if (!$schoolYear instanceof AnneeScolaire || $schoolYear->getEcole()?->getId() !== $school?->getId()) {
            $schoolYear = null;
        }

        $schoolSections = $school instanceof Ecole
            ? array_values(array_filter(
                $sections->findBy(['ecole' => $school], ['nom' => 'ASC']),
                static fn (Section $candidate): bool => !$candidate->isDeleted()
            ))
            : [];
        $sectionId = $input->getInt('section');
        $section = $sectionId > 0 ? $sections->find($sectionId) : null;
        if (
            !$section instanceof Section
            || $section->isDeleted()
            || $section->getEcole()?->getId() !== $school?->getId()
        ) {
            $section = null;
        }

        $schoolClasses = $school instanceof Ecole
            ? array_values(array_filter(
                $classes->findBy(['ecole' => $school], ['nom' => 'ASC']),
                static fn (Classe $candidate): bool => !$candidate->isDeleted()
                    && ($section === null || $candidate->getSection()?->getId() === $section->getId())
            ))
            : [];
        $classId = $input->getInt('classe');
        $class = $classId > 0 ? $classes->find($classId) : null;
        if (
            !$class instanceof Classe
            || $class->isDeleted()
            || $class->getEcole()?->getId() !== $school?->getId()
            || ($section !== null && $class->getSection()?->getId() !== $section->getId())
        ) {
            $class = null;
        }

        $classOptions = $class instanceof Classe
            ? array_values(array_filter(
                $options->findBy(['classe' => $class], ['nom' => 'ASC']),
                static fn (Option $candidate): bool => !$candidate->isDeleted()
            ))
            : [];
        $optionId = $input->getInt('option');
        $option = $optionId > 0 ? $options->find($optionId) : null;
        if (
            !$option instanceof Option
            || $option->isDeleted()
            || $class === null
            || $option->getClasse()?->getId() !== $class->getId()
        ) {
            $option = null;
        }

        return [
            'ecole' => $school,
            'annees' => $schoolYears,
            'annee' => $schoolYear,
            'sections' => $schoolSections,
            'section' => $section,
            'classes' => $schoolClasses,
            'classe' => $class,
            'options' => $classOptions,
            'option' => $option,
        ];
    }

    /**
     * @return array{0: AnneeScolaire, 1: ?Option}
     */
    private function classReportFilters(
        Classe $classe,
        Request $request,
        AnneeScolaireRepository $annees,
        OptionRepository $options
    ): array {
        $yearId = $request->query->getInt('annee');
        $schoolYear = $yearId > 0 ? $annees->find($yearId) : null;
        if (
            $classe->isDeleted()
            || !($classe->getEcole() instanceof Ecole)
            || !($schoolYear instanceof AnneeScolaire)
            || $schoolYear->getEcole()?->getId() !== $classe->getEcole()->getId()
        ) {
            throw $this->createNotFoundException('Année scolaire invalide pour cette classe.');
        }

        $optionId = $request->query->getInt('option');
        $option = $optionId > 0 ? $options->find($optionId) : null;
        if (
            $optionId > 0
            && (!$option instanceof Option || $option->getClasse()?->getId() !== $classe->getId())
        ) {
            throw $this->createNotFoundException('Option invalide pour cette classe.');
        }

        return [$schoolYear, $option];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, int>
     */
    private function filterRouteParameters(array $filters): array
    {
        $parameters = [];
        foreach (['ecole' => 'ecole', 'section' => 'section', 'classe' => 'classe', 'option' => 'option', 'annee' => 'annee'] as $key => $filter) {
            if ($filters[$filter] !== null) {
                $parameters[$key] = $filters[$filter]->getId();
            }
        }

        return $parameters;
    }

    /**
     * @param BulletinAnnuel[] $bulletins
     * @return array<int, array{key: string, semestre: string, libelle: string, ordre: int}>
     */
    private function periodHeaders(array $bulletins): array
    {
        $headers = [];
        foreach ($bulletins as $bulletin) {
            foreach ($bulletin->getDetails() as $course) {
                foreach ($course['evaluations'] as $evaluation) {
                    $storedKey = trim((string) $evaluation['periode']);
                    $key = $this->normalizedPeriodKey($storedKey);
                    $semester = trim((string) ($evaluation['semestre'] ?? ''));
                    $label = trim((string) ($evaluation['libelle'] ?? ''));
                    if ($semester === '' && $storedKey !== $key && str_contains($storedKey, ' — ')) {
                        [$semester] = explode(' — ', $storedKey, 2);
                    }
                    if ($semester === '' && str_contains($key, ' — ')) {
                        [$semester, $legacyLabel] = explode(' — ', $key, 2);
                        $label = $label !== '' ? $label : $legacyLabel;
                    }
                    $semester = $this->normalizeSemesterHeader($semester);
                    $order = (int) ($evaluation['ordre'] ?? $this->periodOrder($key));
                    $headers[$key] = [
                        'key' => $key,
                        'semestre' => $semester !== '' ? $semester : 'Périodes autorisées',
                        'libelle' => $label !== '' ? $label : $key,
                        'ordre' => min($order, $headers[$key]['ordre'] ?? PHP_INT_MAX),
                    ];
                }
            }
        }
        $periods = array_values($headers);
        usort($periods, static function (array $first, array $second): int {
            return ($first['ordre'] <=> $second['ordre']) ?: strnatcasecmp($first['key'], $second['key']);
        });

        return $periods;
    }

    /**
     * Hide legacy, unmarked evaluations that were previously included before their period was authorized.
     *
     * @param BulletinAnnuel[] $bulletins
     */
    private function keepCalculatedPeriodsOnly(array $bulletins): array
    {
        $visibleBulletins = [];
        foreach ($bulletins as $bulletin) {
            $details = [];
            $totalCotes = 0;
            $totalBaremes = 0;
            foreach ($bulletin->getDetails() as $course) {
                $evaluations = array_values(array_filter(
                    $course['evaluations'] ?? [],
                    static fn (array $evaluation): bool => ($evaluation['autorisee'] ?? false) === true
                ));
                foreach ($evaluations as &$evaluation) {
                    $storedPeriod = trim((string) ($evaluation['periode'] ?? ''));
                    if (
                        trim((string) ($evaluation['semestre'] ?? '')) === ''
                        && preg_match('/^(?<semester>.+?)\s+[—–-]\s*(?:Période|Examen)\s*\d+\s*$/iu', $storedPeriod, $matches)
                    ) {
                        $evaluation['semestre'] = $this->normalizeSemesterHeader($matches['semester']);
                    }
                    $evaluation['periode'] = $this->normalizedPeriodKey($storedPeriod);
                }
                unset($evaluation);
                $course['evaluations'] = $evaluations;
                [$courseCotes, $courseBaremes] = $this->totalsForEvaluations($evaluations);
                $course['total_cotes'] = $courseCotes;
                $course['total_baremes'] = $courseBaremes;
                $course['pourcentage'] = $courseBaremes > 0
                    ? number_format($courseCotes * 100 / $courseBaremes, 2, '.', '')
                    : '0.00';
                $totalCotes += $courseCotes;
                $totalBaremes += $courseBaremes;
                $details[] = $course;
            }

            $percentage = $totalBaremes > 0
                ? number_format($totalCotes * 100 / $totalBaremes, 2, '.', '')
                : '0.00';
            $bulletin
                ->setDetails($details)
                ->setTotalCotes($totalCotes)
                ->setTotalBaremes($totalBaremes)
                ->setPourcentage($percentage)
                ->setDecision((float) $percentage >= 50 ? 'admis' : 'ajourne');
            if ($totalBaremes > 0) {
                $visibleBulletins[] = $bulletin;
            }
        }

        return $visibleBulletins;
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

    private function normalizeSemesterHeader(string $semester): string
    {
        return match (mb_strtolower(trim($semester))) {
            'semestre i', '1er semestre' => '1ER SEMESTRE',
            'semestre ii', 'second semestre', '2ème semestre', '2eme semestre' => 'SECOND SEMESTRE',
            'trimestre i', '1er trimestre' => '1ER TRIMESTRE',
            'trimestre ii', '2ème trimestre', '2eme trimestre' => '2ÈME TRIMESTRE',
            'trimestre iii', '3ème trimestre', '3eme trimestre' => '3ÈME TRIMESTRE',
            default => $semester,
        };
    }

    private function normalizedPeriodKey(string $period): string
    {
        if (preg_match('/(?<period>(?:Période|Examen)\s*\d+)\s*$/iu', $period, $matches)) {
            return trim($matches['period']);
        }

        return trim($period);
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
}
