<?php

namespace App\Controller;

use App\Entity\AnneeScolaire;
use App\Entity\AutorisationCotation;
use App\Entity\Classe;
use App\Entity\ConfigurationBulletin;
use App\Entity\Cours;
use App\Entity\Ecole;
use App\Entity\Evaluation;
use App\Entity\Option;
use App\Entity\Section;
use App\Repository\AnneeScolaireRepository;
use App\Repository\AutorisationCotationRepository;
use App\Repository\ClasseRepository;
use App\Repository\ConfigurationBulletinRepository;
use App\Repository\ConfigurationFicheCotationRepository;
use App\Repository\CoursRepository;
use App\Repository\EcoleRepository;
use App\Repository\EvaluationRepository;
use App\Repository\NoteRepository;
use App\Repository\SectionRepository;
use App\Service\CotationStructure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/fiche-cotation/configurer', name: 'app_fiche_cotation_config_')]
#[IsGranted('ROLE_ADMIN')]
final class ConfigurationCotationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    #[Route('/autorisations', name: 'autorisations', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EcoleRepository $ecoleRepository,
        SectionRepository $sectionRepository,
        ClasseRepository $classeRepository,
        AnneeScolaireRepository $anneeRepository,
        ConfigurationBulletinRepository $configurationRepository,
        ConfigurationFicheCotationRepository $ficheConfigurationRepository,
        CoursRepository $coursRepository,
        EvaluationRepository $evaluationRepository,
        NoteRepository $noteRepository,
        AutorisationCotationRepository $autorisationRepository,
        CotationStructure $cotationStructure,
        EntityManagerInterface $entityManager
    ): Response {
        $action = $request->request->get('action');
        if ($request->isMethod('POST') && $action === 'save_authorisations') {
            $structure = $request->request->get('structure');
            if (!is_string($structure) || !in_array($structure, ['semestre', 'trimestre'], true)) {
                throw $this->createAccessDeniedException('La structure envoyée est invalide.');
            }
            if (!$this->isCsrfTokenValid(
                'autorisation_cotation_' . $structure,
                (string) $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException('Le formulaire d’autorisation est invalide.');
            }

            $checked = $request->request->all()['autorisations'] ?? [];
            if (!is_array($checked)) {
                throw $this->createAccessDeniedException('Les autorisations envoyées sont invalides.');
            }
            $periodNames = $this->nomsPeriodes($cotationStructure->groupes($structure));
            foreach ($checked as $name => $value) {
                if (!is_string($name) || !in_array($name, $periodNames, true) || !in_array($value, ['1', 1], true)) {
                    throw $this->createAccessDeniedException('Une période d’autorisation est invalide.');
                }
            }

            foreach ($periodNames as $periodName) {
                $authorization = $autorisationRepository->findOneBy([
                    'structure' => $structure,
                    'periode' => $periodName,
                ]) ?? new AutorisationCotation($structure, $periodName);
                $authorization->setAutorisee(isset($checked[$periodName]));
                $entityManager->persist($authorization);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Les périodes autorisées ont été enregistrées.');

            $redirectParameters = $request->query->all();
            $redirectParameters['structure'] = $structure;

            return $this->redirectToRoute('app_fiche_cotation_config_autorisations', $redirectParameters);
        }

        $filters = $this->filters(
            $request,
            $ecoleRepository,
            $sectionRepository,
            $classeRepository,
            $anneeRepository,
            $configurationRepository,
            $ficheConfigurationRepository,
            $coursRepository,
            $cotationStructure
        );

        if ($request->isMethod('POST') && $action === 'save_course_maxima') {
            try {
                $this->saveCourseMaxima($request, $filters, $evaluationRepository, $noteRepository, $entityManager);
            } catch (\DomainException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute('app_fiche_cotation_config_index', $filters['query']);
            }
            $this->addFlash('success', 'Les barèmes des cours ont été enregistrés.');

            return $this->redirectToRoute('app_fiche_cotation_index');
        }

        $authorizationStructure = $request->query->get('structure');
        if (!in_array($authorizationStructure, ['semestre', 'trimestre'], true)) {
            $authorizationStructure = $filters['ficheConfiguration']?->getStructure() ?? 'semestre';
        }
        $authorizationGroups = $cotationStructure->groupes($authorizationStructure);
        $authorizationValues = $autorisationRepository->autorisationsPourStructure($authorizationStructure);
        $authorizationPeriodCount = count($this->nomsPeriodes($authorizationGroups));
        $enabledAuthorizationCount = count(array_filter(
            $authorizationValues,
            static fn (bool $enabled): bool => $enabled
        ));
        $matrix = $this->courseMatrix($filters, $evaluationRepository);
        $authorizationPage = $request->attributes->get('_route') === 'app_fiche_cotation_config_autorisations';

        return $this->render('fiche_cotation/configuration.html.twig', [
            ...$filters,
            'authorization_page' => $authorizationPage,
            'groupes_colonnes' => $filters['ficheConfiguration'] === null
                ? []
                : $cotationStructure->groupes($filters['ficheConfiguration']->getStructure()),
            'groupes_autorisation' => $authorizationGroups,
            'autorisations' => $authorizationValues,
            'nombre_periodes_autorisation' => $authorizationPeriodCount,
            'nombre_periodes_autorisees' => $enabledAuthorizationCount,
            'matrice_cours' => $matrix,
            'structure_autorisation' => $authorizationStructure,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(
        Request $request,
        EcoleRepository $ecoleRepository,
        SectionRepository $sectionRepository,
        ClasseRepository $classeRepository,
        AnneeScolaireRepository $anneeRepository,
        ConfigurationBulletinRepository $configurationRepository,
        ConfigurationFicheCotationRepository $ficheConfigurationRepository,
        CoursRepository $coursRepository,
        CotationStructure $cotationStructure
    ): array {
        $ecoles = $ecoleRepository->findBy([], ['nom' => 'ASC']);
        $ecole = $this->findSelection($request, 'ecole', $ecoleRepository);
        $sections = $ecole === null ? [] : $sectionRepository->findBy(['ecole' => $ecole], ['nom' => 'ASC']);
        $section = $this->findSelection($request, 'section', $sectionRepository);
        if ($section !== null && ($ecole === null || $section->getEcole()?->getId() !== $ecole->getId())) {
            $section = null;
        }

        $classes = $section === null ? [] : $classeRepository->findBy(['section' => $section], ['nom' => 'ASC']);
        $classe = $this->findSelection($request, 'classe', $classeRepository);
        if ($classe !== null && ($section === null || $classe->getSection()?->getId() !== $section->getId())) {
            $classe = null;
        }

        $annees = $ecole === null ? [] : $anneeRepository->findBy(['ecole' => $ecole], ['dateDebut' => 'DESC']);
        $annee = $this->findSelection($request, 'annee', $anneeRepository);
        if ($annee !== null && ($ecole === null || $annee->getEcole()?->getId() !== $ecole->getId())) {
            $annee = null;
        }
        if ($annee === null && $annees !== []) {
            $annee = $this->anneeCouranteOuDerniere($annees);
        }

        $optionValue = $request->query->get('option');
        $optionId = is_scalar($optionValue) && ctype_digit((string) $optionValue) ? (int) $optionValue : null;
        $options = $classe === null ? [] : $classe->getOptions()->toArray();
        $hasNoOption = false;
        if ($classe !== null) {
            foreach ($coursRepository->findBy(['classe' => $classe]) as $course) {
                if ($course->getOption() === null && !$course->isDeleted()) {
                    $hasNoOption = true;
                    break;
                }
            }
        }
        if (
            $optionId !== null
            && !(($optionId === 0 && $hasNoOption) || $this->containsId($options, $optionId))
        ) {
            $optionId = null;
        }
        $option = $optionId !== null && $optionId > 0
            ? $this->findOption($options, $optionId)
            : null;

        $cours = [];
        if ($classe !== null) {
            $cours = array_values(array_filter(
                $coursRepository->findBy(['classe' => $classe], ['nom' => 'ASC']),
                static fn (Cours $course): bool => !$course->isDeleted()
                    && (
                        $optionId === null
                        || ($optionId === 0
                            ? $course->getOption() === null
                            : $course->getOption()?->getId() === $optionId)
                    )
            ));
        }

        $ficheConfiguration = $ecole === null
            ? null
            : $ficheConfigurationRepository->findOneBy(['ecole' => $ecole]);
        $groupes = $ficheConfiguration?->getStructure() === null
            ? []
            : $cotationStructure->groupes($ficheConfiguration->getStructure());

        $configurations = [];
        if ($ecole !== null && $classe !== null && $annee !== null) {
            foreach ($configurationRepository->findBy([
                'ecole' => $ecole,
                'classe' => $classe,
                'anneeScolaire' => $annee,
            ]) as $configuration) {
                $key = $this->cleConfiguration($configuration->getNom());
                if ($key !== null) {
                    $configurationOptionId = $configuration->getOptions()?->getId() ?? 0;
                    $configurations[$key][$configurationOptionId][] = $configuration;
                }
            }
        }

        $periodsConfigured = true;
        foreach ($groupes as $group) {
            foreach ($cours as $course) {
                $key = $this->cleConfiguration($group['configuration']);
                $courseOptionId = $course->getOption()?->getId() ?? 0;
                $matchingConfigurations = $key === null ? [] : ($configurations[$key][$courseOptionId] ?? []);
                if (count($matchingConfigurations) !== 1) {
                    $periodsConfigured = false;
                    break 2;
                }
            }
        }

        $query = [
            'ecole' => $ecole?->getId(),
            'section' => $section?->getId(),
            'classe' => $classe?->getId(),
            'option' => $optionId,
            'annee' => $annee?->getId(),
        ];

        return compact(
            'ecoles',
            'ecole',
            'sections',
            'section',
            'classes',
            'classe',
            'annees',
            'annee',
            'options',
            'option',
            'optionId',
            'hasNoOption',
            'cours',
            'ficheConfiguration',
            'groupes',
            'configurations',
            'periodsConfigured',
            'query'
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array{cours: Cours, evaluations: array<string, array<int, Evaluation>>, totaux: array<string, ?int>}>
     */
    private function courseMatrix(array $filters, EvaluationRepository $evaluationRepository): array
    {
        $matrix = [];
        foreach ($filters['cours'] as $course) {
            $matrix[$course->getId()] = ['cours' => $course, 'evaluations' => [], 'totaux' => []];
            foreach ($filters['groupes'] as $group) {
                $configurationKey = $this->cleConfiguration($group['configuration']);
                $optionId = $course->getOption()?->getId() ?? 0;
                $matchingConfigurations = $configurationKey === null
                    ? []
                    : ($filters['configurations'][$configurationKey][$optionId] ?? []);
                if (count($matchingConfigurations) > 1) {
                    throw new \DomainException(sprintf(
                        'Plusieurs configurations existent pour %s — %s. Corrigez les doublons avant de configurer les maxima.',
                        $course->getNom(),
                        $group['configuration']
                    ));
                }
                $configuration = $matchingConfigurations[0] ?? null;
                $groupTotal = 0;
                $hasMaximum = false;
                $configurationEvaluations = $configuration instanceof ConfigurationBulletin
                    ? $evaluationRepository->findBy([
                        'cours' => $course,
                        'configurationBulletin' => $configuration,
                    ])
                    : [];
                foreach ($group['periodes'] as $period) {
                    $matrix[$course->getId()]['evaluations'][$group['configuration']][$period['nom']] = [];
                    foreach ($configurationEvaluations as $evaluation) {
                        if (strcasecmp(trim((string) $evaluation->getNom()), $period['nom']) === 0) {
                            $matrix[$course->getId()]['evaluations'][$group['configuration']][$period['nom']][] = $evaluation;
                            $groupTotal += $evaluation->getBareme() ?? 0;
                            $hasMaximum = true;
                        }
                    }
                }
                $matrix[$course->getId()]['totaux'][$group['configuration']] = $hasMaximum ? $groupTotal : null;
            }
        }

        return array_values($matrix);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function saveCourseMaxima(
        Request $request,
        array $filters,
        EvaluationRepository $evaluationRepository,
        NoteRepository $noteRepository,
        EntityManagerInterface $entityManager
    ): void {
        if (!$this->isCsrfTokenValid('configuration_maxima_cotation', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Le jeton de sécurité est invalide.');
        }
        if (
            !$filters['ecole'] instanceof Ecole
            || !$filters['classe'] instanceof Classe
            || !$filters['annee'] instanceof AnneeScolaire
            || $filters['cours'] === []
            || $filters['ficheConfiguration'] === null
        ) {
            throw $this->createAccessDeniedException('Sélectionnez une école, une classe, une année et un modèle de cotation avant de configurer les maxima.');
        }

        $submitted = $request->request->all()['baremes'] ?? [];
        if (!is_array($submitted)) {
            throw $this->createAccessDeniedException('Les barèmes envoyés sont invalides.');
        }

        $matrix = $this->courseMatrix($filters, $evaluationRepository);
        $courseIds = array_map(static fn (array $row): int => $row['cours']->getId(), $matrix);
        foreach (array_keys($submitted) as $submittedCourseId) {
            if (!ctype_digit((string) $submittedCourseId) || !in_array((int) $submittedCourseId, $courseIds, true)) {
                throw $this->createAccessDeniedException('Un cours envoyé ne correspond pas aux filtres sélectionnés.');
            }
        }
        $existingEvaluations = [];
        foreach ($matrix as $courseRow) {
            foreach ($courseRow['evaluations'] as $periods) {
                foreach ($periods as $evaluations) {
                    foreach ($evaluations as $evaluation) {
                        $existingEvaluations[$evaluation->getId()] = $evaluation;
                    }
                }
            }
        }
        $maximumGradeByEvaluation = [];
        if ($existingEvaluations !== []) {
            foreach ($noteRepository->findBy(['evaluation' => array_values($existingEvaluations)]) as $note) {
                $evaluationId = $note->getEvaluation()?->getId();
                if ($evaluationId !== null) {
                    $maximumGradeByEvaluation[$evaluationId] = max(
                        $maximumGradeByEvaluation[$evaluationId] ?? 0,
                        (int) $note->getCote()
                    );
                }
            }
        }
        $updates = [];
        foreach ($matrix as $courseRow) {
            $course = $courseRow['cours'];
            $courseValues = $submitted[$course->getId()] ?? [];
            if (!is_array($courseValues)) {
                throw $this->createAccessDeniedException('Une ligne de barème est invalide.');
            }
            foreach ($filters['groupes'] as $group) {
                $groupValues = $courseValues[$group['configuration']] ?? [];
                if (!is_array($groupValues)) {
                    throw $this->createAccessDeniedException('Un groupe de barèmes est invalide.');
                }
                $configurationKey = $this->cleConfiguration($group['configuration']);
                if ($configurationKey === null) {
                    throw new \LogicException('Le nom de période de cotation est invalide.');
                }
                $optionId = $course->getOption()?->getId() ?? 0;
                $configurationMatches = $filters['configurations'][$configurationKey][$optionId] ?? [];
                if (count($configurationMatches) > 1) {
                    throw new \DomainException(sprintf(
                        'Plusieurs configurations existent pour %s — %s.',
                        $course->getNom(),
                        $group['configuration']
                    ));
                }
                $configuration = $configurationMatches[0] ?? null;
                foreach ($group['periodes'] as $period) {
                    if (!array_key_exists($period['nom'], $groupValues)) {
                        continue;
                    }
                    $raw = $groupValues[$period['nom']];
                    if (!is_scalar($raw) || trim((string) $raw) === '') {
                        continue;
                    }
                    $maximum = filter_var(trim((string) $raw), FILTER_VALIDATE_INT);
                    if ($maximum === false || $maximum < 1 || $maximum > 1000) {
                        throw new \DomainException(sprintf(
                            'Le maximum pour %s — %s doit être un entier entre 1 et 1000.',
                            $course->getNom(),
                            $period['libelle']
                        ));
                    }

                    $evaluations = $courseRow['evaluations'][$group['configuration']][$period['nom']] ?? [];
                    if (count($evaluations) > 1) {
                        throw new \DomainException(sprintf(
                            'Plusieurs cotations existent déjà pour %s — %s. Corrigez les doublons avant de modifier le maximum.',
                            $course->getNom(),
                            $period['libelle']
                        ));
                    }
                    if (!$configuration instanceof ConfigurationBulletin) {
                        $configuration = (new ConfigurationBulletin())
                            ->setNom($group['configuration'])
                            ->setStatut('brouillon')
                            ->setEcole($filters['ecole'])
                            ->setAnneeScolaire($filters['annee'])
                            ->setClasse($filters['classe'])
                            ->setOptions($course->getOption());
                        $entityManager->persist($configuration);
                        $filters['configurations'][$configurationKey][$optionId][] = $configuration;
                    }
                    $evaluation = $evaluations[0] ?? (new Evaluation())
                        ->setNom($period['nom'])
                        ->setType('Cotation')
                        ->setCoefficient(1)
                        ->setCours($course)
                        ->setConfigurationBulletin($configuration);

                    $evaluationId = $evaluation->getId();
                    if ($evaluationId !== null && ($maximumGradeByEvaluation[$evaluationId] ?? 0) > $maximum) {
                        throw new \DomainException(sprintf(
                            'Le maximum de %s ne peut pas être inférieur à une cote déjà saisie (%d).',
                            $course->getNom(),
                            $maximumGradeByEvaluation[$evaluationId]
                        ));
                    }
                    $updates[] = [$evaluation, $maximum];
                }
            }
        }

        foreach ($updates as [$evaluation, $maximum]) {
            $evaluation->setBareme($maximum);
            if ($evaluation->getId() === null) {
                $entityManager->persist($evaluation);
            }
        }
        $entityManager->flush();
    }

    /**
     * @param array<int, array{periodes: array<int, array{nom: string}>}> $groups
     * @return string[]
     */
    private function nomsPeriodes(array $groups): array
    {
        $names = [];
        foreach ($groups as $group) {
            foreach ($group['periodes'] as $period) {
                $names[] = $period['nom'];
            }
        }

        return array_values(array_unique($names));
    }

    private function findSelection(Request $request, string $key, ObjectRepository $repository): ?object
    {
        $id = $request->query->getInt($key);
        if ($id < 1) {
            return null;
        }
        $entity = $repository->find($id);
        if ($entity === null) {
            throw $this->createNotFoundException(sprintf('La sélection %s est introuvable.', $key));
        }

        return $entity;
    }

    /**
     * @param array<int, Option> $options
     */
    private function containsId(array $options, int $id): bool
    {
        return $this->findOption($options, $id) !== null;
    }

    /**
     * @param array<int, Option> $options
     */
    private function findOption(array $options, int $id): ?Option
    {
        foreach ($options as $option) {
            if ($option->getId() === $id) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @param AnneeScolaire[] $annees
     */
    private function anneeCouranteOuDerniere(array $annees): AnneeScolaire
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone('Africa/Kinshasa'));
        foreach ($annees as $annee) {
            $start = $annee->getDateDebut();
            $end = $annee->getDateFin();
            if ($start !== null && $end !== null && $start <= $today && $end >= $today) {
                return $annee;
            }
        }

        return $annees[0];
    }

    private function cleConfiguration(?string $nom): ?string
    {
        if ($nom === null || preg_match('/^(Semestre|Trimestre)\s+(I{1,3}|[1-3])$/iu', trim($nom), $matches) !== 1) {
            return null;
        }

        $number = match (strtoupper($matches[2])) {
            'I', '1' => 1,
            'II', '2' => 2,
            'III', '3' => 3,
        };
        $type = strtolower($matches[1]);
        if (($type === 'semestre' && $number > 2) || ($type === 'trimestre' && $number > 3)) {
            return null;
        }

        return $type . '-' . $number;
    }
}
