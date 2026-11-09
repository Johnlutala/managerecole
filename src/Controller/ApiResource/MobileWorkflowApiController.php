<?php

namespace App\Controller\ApiResource;

use App\Entity\AnneeScolaire;
use App\Entity\BulletinAnnuel;
use App\Entity\Classe;
use App\Entity\Cours;
use App\Entity\Eleve;
use App\Entity\Ecole;
use App\Entity\Evaluation;
use App\Entity\Note;
use App\Entity\Parents;
use App\Entity\PreparationCours;
use App\Entity\Professeur;
use App\Entity\User;
use App\Repository\AnneeScolaireRepository;
use App\Repository\AutorisationCotationRepository;
use App\Repository\BulletinAnnuelRepository;
use App\Repository\ConfigurationFicheCotationRepository;
use App\Repository\ClasseRepository;
use App\Repository\CoursRepository;
use App\Repository\CreneauHoraireRepository;
use App\Repository\EcoleRepository;
use App\Repository\NoteRepository;
use App\Repository\PreparationCoursRepository;
use App\Repository\SectionRepository;
use App\Repository\OptionRepository;
use App\Repository\UserRepository;
use App\Service\BulletinAnnuelCalculator;
use App\Service\CotationStructure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rest/v1/mobile')]
final class MobileWorkflowApiController extends AbstractApiController
{
    #[Route('/parent/profil', name: 'api_mobile_parent_profile', methods: ['GET'])]
    public function parentProfile(Request $request): JsonResponse
    {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Profil parent', $auth['code']);
        }

        $parent = $auth['user']->getParent();
        if (!$this->userHasAnyRole($auth['user'], ['ROLE_PARENT']) || !$parent instanceof Parents) {
            return $this->error('Cet espace est réservé aux comptes parents.', [], 'Profil parent', Response::HTTP_FORBIDDEN);
        }

        return $this->success([
            'parent' => $this->parentPayload($parent),
            'enfants' => array_map(fn(Eleve $eleve): array => $this->childPayload($eleve), $parent->getEleve()->toArray()),
        ], 'Profil parent');
    }

    #[Route('/parent/enfants/{id}', name: 'api_mobile_parent_child', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function parentChild(
        Request $request,
        Eleve $eleve,
        CreneauHoraireRepository $slots
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Dossier enfant parent', $auth['code']);
        }

        $parent = $auth['user']->getParent();
        if (
            !$this->userHasAnyRole($auth['user'], ['ROLE_PARENT'])
            || !$parent instanceof Parents
            || !$parent->getEleve()->contains($eleve)
        ) {
            return $this->error('Cet enfant n’est pas rattaché à votre compte parent.', [], 'Dossier enfant parent', Response::HTTP_FORBIDDEN);
        }

        $presences = array_values(array_filter(
            $eleve->getPresences()->toArray(),
            static fn($presence): bool => $presence->getValidatedAt() !== null
        ));
        usort($presences, static fn($a, $b): int => $b->getDate() <=> $a->getDate());

        return $this->success([
            'eleve' => $this->childPayload($eleve),
            'emploiDuTemps' => $this->scheduleForStudent($eleve, $slots),
            'presences' => array_map(static fn($presence): array => [
                'date' => $presence->getDate()?->format('Y-m-d'),
                'statut' => $presence->getStatut(),
                'heureArrivee' => $presence->getHeureArrivee()?->format('H:i'),
                'heureDepart' => $presence->getHeureDepart()?->format('H:i'),
                'observation' => $presence->getObservation(),
            ], $presences),
            'notes' => $this->validatedNotesForStudent($eleve),
        ], 'Dossier enfant parent');
    }

    #[Route('/eleve/profil', name: 'api_mobile_student_profile', methods: ['GET'])]
    public function studentProfile(
        Request $request,
        CreneauHoraireRepository $slots
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Dossier élève', $auth['code']);
        }
        $eleve = $auth['user']->getEleve();
        if (!$this->userHasAnyRole($auth['user'], ['ROLE_ELEVE']) || !$eleve instanceof Eleve) {
            return $this->error('Cet espace est réservé aux comptes élèves.', [], 'Dossier élève', Response::HTTP_FORBIDDEN);
        }

        $presences = array_values(array_filter(
            $eleve->getPresences()->toArray(),
            static fn($presence): bool => $presence->getValidatedAt() !== null
        ));
        usort($presences, static fn($a, $b): int => $b->getDate() <=> $a->getDate());

        return $this->success([
            'eleve' => $this->childPayload($eleve),
            'emploiDuTemps' => $this->scheduleForStudent($eleve, $slots),
            'notes' => $this->validatedNotesForStudent($eleve),
            'presences' => array_map(static fn($presence): array => [
                'date' => $presence->getDate()?->format('Y-m-d'),
                'statut' => $presence->getStatut(),
                'heureArrivee' => $presence->getHeureArrivee()?->format('H:i'),
                'heureDepart' => $presence->getHeureDepart()?->format('H:i'),
                'observation' => $presence->getObservation(),
            ], $presences),
        ], 'Dossier élève');
    }


    #[Route('/professeur/cotations', name: 'api_mobile_teacher_grades', methods: ['GET'])]
    public function teacherGrades(
        Request $request,
        CoursRepository $courses
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);

        if (!$auth['status']) {
            return $this->error(
                $auth['message'],
                [],
                'Cotations professeur',
                $auth['code']
            );
        }

        $professeur = $auth['user']->getProfesseur();

        if (
            !$this->userHasAnyRole($auth['user'], ['ROLE_PROFESSEUR'])
            || !$professeur instanceof Professeur
        ) {
            return $this->error(
                'Cet espace est réservé aux comptes professeurs.',
                [],
                'Cotations professeur',
                Response::HTTP_FORBIDDEN
            );
        }

        $limit = min(200, max(1, $request->query->getInt('limit', 100)));

        // 1. Récupérer tous les cours attribués au professeur.
        // Cette requête fonctionne même si aucune cotation n'existe.
        $assignedCourses = $courses->createQueryBuilder('cours')
            ->leftJoin('cours.classe', 'classe')
            ->addSelect('classe')
            ->leftJoin('cours.option', 'opt')
            ->addSelect('opt')
            ->andWhere('cours.professeur = :professeur')
            ->setParameter('professeur', $professeur)
            ->orderBy('classe.nom', 'ASC')
            ->addOrderBy('cours.nom', 'ASC')
            ->getQuery()
            ->getResult();

        $assignedCourses = array_values(array_filter(
            $assignedCourses,
            static fn(Cours $cours): bool => !$cours->isDeleted()
        ));

        // 2. Récupérer les cotations déjà enregistrées.
        $notes = $this->em->getRepository(Note::class)
            ->createQueryBuilder('note')
            ->addSelect('evaluation', 'cours', 'classe', 'eleve')
            ->innerJoin('note.evaluation', 'evaluation')
            ->innerJoin('evaluation.cours', 'cours')
            ->innerJoin('cours.classe', 'classe')
            ->innerJoin('note.eleve', 'eleve')
            ->andWhere('cours.professeur = :professeur')
            ->setParameter('professeur', $professeur)
            ->orderBy('evaluation.id', 'DESC')
            ->addOrderBy('eleve.nom', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->success([
            'limite' => $limit,

            // Tous les cours du professeur.
            'cours' => array_map(
                static fn(Cours $cours): array => [
                    'id' => $cours->getId(),
                    'nom' => $cours->getNom(),

                    'classe' => [
                        'id' => $cours->getClasse()?->getId(),
                        'nom' => $cours->getClasse()?->getNom(),
                    ],

                    'option' => $cours->getOption() ? [
                        'id' => $cours->getOption()->getId(),
                        'nom' => $cours->getOption()->getNom(),
                    ] : null,

                    'ecole' => [
                        'id' => $cours->getClasse()?->getEcole()?->getId(),
                        'nom' => $cours->getClasse()?->getEcole()?->getNom(),
                    ],
                ],
                $assignedCourses
            ),

            // Cotations déjà enregistrées.
            'cotations' => array_map(
                static fn(Note $note): array => [
                    'id' => $note->getId(),
                    'cote' => $note->getCote(),
                    'observation' => $note->getObservation(),
                    'statut' => $note->getStatut(),
                    'dateSoumission' => $note->getDateSoumission()?->format(DATE_ATOM),

                    'eleve' => [
                        'id' => $note->getEleve()?->getId(),
                        'matricule' => $note->getEleve()?->getMatricule(),
                        'nom' => trim(implode(' ', array_filter([
                            $note->getEleve()?->getNom(),
                            $note->getEleve()?->getPostnom(),
                            $note->getEleve()?->getPrenom(),
                        ]))),
                    ],

                    'cours' => [
                        'id' => $note->getEvaluation()?->getCours()?->getId(),
                        'nom' => $note->getEvaluation()?->getCours()?->getNom(),
                        'classe' => $note->getEvaluation()?->getCours()?->getClasse()?->getNom(),
                    ],

                    'evaluation' => [
                        'id' => $note->getEvaluation()?->getId(),
                        'nom' => $note->getEvaluation()?->getNom(),
                        'bareme' => $note->getEvaluation()?->getBareme(),
                        'date' => $note->getEvaluation()?->getDateEvaluation()?->format('Y-m-d'),
                    ],
                ],
                $notes
            ),
        ], 'Cotations professeur');
    }


    #[Route('/professeur/cotations', name: 'api_mobile_teacher_grades_save', methods: ['POST'])]
    public function saveTeacherGrades(
        Request $request,
        CoursRepository $courses,
        ConfigurationFicheCotationRepository $ficheConfigurations,
        AutorisationCotationRepository $authorizations,
        CotationStructure $cotationStructure,
        NoteRepository $notes,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Enregistrer les cotations', $auth['code']);
        }
        $professeur = $auth['user']->getProfesseur();
        if (!$this->userHasAnyRole($auth['user'], ['ROLE_PROFESSEUR']) || !$professeur instanceof Professeur) {
            return $this->error('Cet espace est réservé aux comptes professeurs.', [], 'Enregistrer les cotations', Response::HTTP_FORBIDDEN);
        }
        $data = $this->getJsonData($request);
        $coursId = filter_var($data['coursId'] ?? null, FILTER_VALIDATE_INT);
        $cours = $coursId ? $courses->find($coursId) : null;
        if (!$cours instanceof Cours || $cours->getProfesseur()?->getId() !== $professeur->getId() || $cours->isDeleted()) {
            return $this->error('Le cours est invalide ou ne vous est pas attribué.', [], 'Enregistrer les cotations', Response::HTTP_FORBIDDEN);
        }
        $action = $data['action'] ?? 'enregistrer';
        $mode = $data['mode'] ?? 'noter';
        $grades = $data['notes'] ?? null;
        if (
            !in_array($action, ['enregistrer', 'soumettre'], true)
            || !in_array($mode, ['noter', 'modifier'], true)
            || !is_array($grades)
            || $grades === []
            || count($grades) > 50
        ) {
            return $this->error(
                'Envoyez un cours, un mode valide et une liste non vide (maximum 50 évaluations) dans "notes".',
                [],
                'Enregistrer les cotations',
                Response::HTTP_BAD_REQUEST
            );
        }

        $classe = $cours->getClasse();
        $ecole = $classe?->getEcole();
        if ($classe === null || $ecole === null) {
            return $this->error('La classe ou l’école du cours n’est pas renseignée.', [], 'Enregistrer les cotations', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $fiche = $ficheConfigurations->findOneBy(['ecole' => $ecole]);
        $structure = $fiche?->getStructure();
        if ($structure === null) {
            return $this->error('Le modèle de cotation de l’école n’est pas configuré.', [], 'Enregistrer les cotations', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $periods = [];
        foreach ($cotationStructure->groupes($structure) as $group) {
            foreach ($group['periodes'] as $period) {
                $periods[$period['nom']] = true;
            }
        }
        $openPeriods = $authorizations->autorisationsPourStructure($structure);
        $students = $this->em->getRepository(Eleve::class)->createQueryBuilder('eleve')
            ->andWhere('eleve.classe = :classe')
            ->andWhere('eleve.statut = :actif')
            ->andWhere('eleve.deleted = :deleted')
            ->setParameter('classe', $classe)
            ->setParameter('actif', true)
            ->setParameter('deleted', false)
            ->getQuery()
            ->getResult();
        if ($cours->getOption() !== null) {
            $students = array_values(array_filter(
                $students,
                static fn(Eleve $eleve): bool => $eleve->getOption()?->getId() === $cours->getOption()?->getId()
            ));
        }
        $studentsById = [];
        foreach ($students as $student) {
            $studentsById[$student->getId()] = $student;
        }

        $evaluationGrades = [];
        $gradeCount = 0;
        foreach ($grades as $evaluationId => $studentGrades) {
            if (!ctype_digit((string) $evaluationId) || !is_array($studentGrades) || $studentGrades === []) {
                return $this->error('La liste des évaluations ou des élèves est invalide.', [], 'Enregistrer les cotations', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $evaluation = $this->em->getRepository(Evaluation::class)->find((int) $evaluationId);
            $configuration = $evaluation?->getConfigurationBulletin();
            if (
                !$evaluation instanceof Evaluation
                || $evaluation->getCours()?->getId() !== $cours->getId()
                || $configuration?->getClasse()?->getId() !== $classe->getId()
                || $configuration?->getEcole()?->getId() !== $ecole->getId()
                || ($configuration?->getOptions() !== null
                    && $configuration->getOptions()?->getId() !== $cours->getOption()?->getId())
                || ($professeur->getAnneeScolaire() !== null
                    && $configuration?->getAnneeScolaire()?->getId() !== $professeur->getAnneeScolaire()?->getId())
                || !isset($periods[$evaluation->getNom()])
                || !($openPeriods[$evaluation->getNom()] ?? false)
            ) {
                return $this->error('Une évaluation ne fait pas partie des périodes ouvertes de ce cours.', [], 'Enregistrer les cotations', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $duplicatePeriods = array_filter(
                $configuration?->getEvaluations()?->toArray() ?? [],
                static fn(Evaluation $item): bool => $item->getCours()?->getId() === $cours->getId()
                    && strcasecmp(trim((string) $item->getNom()), trim((string) $evaluation->getNom())) === 0
            );
            if (count($duplicatePeriods) !== 1 || ($evaluation->getBareme() ?? 0) < 1) {
                return $this->error('La configuration de cette évaluation est ambiguë ou son barème est invalide.', [], 'Enregistrer les cotations', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            foreach ($studentGrades as $studentId => $grade) {
                if (!ctype_digit((string) $studentId) || !isset($studentsById[(int) $studentId]) || !is_scalar($grade)) {
                    return $this->error('Un élève envoyé ne fait pas partie de cette classe ou sa cote est invalide.', [], 'Enregistrer les cotations', Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                if (trim((string) $grade) !== '' && (
                    !preg_match('/^\d+$/D', trim((string) $grade))
                    || (int) $grade > $evaluation->getBareme()
                )) {
                    return $this->error(sprintf('Chaque cote doit être un entier compris entre 0 et %d.', $evaluation->getBareme()), [], 'Enregistrer les cotations', Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                ++$gradeCount;
                if ($gradeCount > 1000) {
                    return $this->error('La requête dépasse le maximum de 1000 cotes.', [], 'Enregistrer les cotations', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
                }
            }
            $evaluationGrades[$evaluation->getId()] = [$evaluation, $studentGrades];
        }

        $existing = $notes->findForEvaluationsAndEleves(
            array_map(static fn(array $entry): Evaluation => $entry[0], $evaluationGrades),
            array_values($studentsById)
        );
        $existingByKey = [];
        foreach ($existing as $note) {
            $existingByKey[$note->getEvaluation()?->getId() . ':' . $note->getEleve()?->getId()] = $note;
        }

        $changes = [];
        foreach ($evaluationGrades as [$evaluation, $studentGrades]) {
            foreach ($studentGrades as $studentId => $value) {
                $key = $evaluation->getId() . ':' . (int) $studentId;
                $note = $existingByKey[$key] ?? null;
                $raw = trim((string) $value);
                if ($note instanceof Note && in_array($note->getStatut(), ['soumise', 'validee'], true)) {
                    if ($raw !== (string) $note->getCote()) {
                        return $this->error('Une note soumise ou validée ne peut plus être modifiée.', [], 'Enregistrer les cotations', Response::HTTP_CONFLICT);
                    }
                    continue;
                }
                if ($mode === 'noter' && $note instanceof Note) {
                    if ($raw !== (string) $note->getCote()) {
                        return $this->error('Cette note existe déjà. Utilisez le mode "modifier" pour une cote brouillon.', [], 'Enregistrer les cotations', Response::HTTP_CONFLICT);
                    }
                    continue;
                }
                if ($mode === 'modifier' && !$note instanceof Note && $raw !== '') {
                    return $this->error('Le mode "modifier" ne peut pas créer de nouvelles notes.', [], 'Enregistrer les cotations', Response::HTTP_CONFLICT);
                }
                if ($raw === '') {
                    if ($note instanceof Note && $mode === 'modifier') {
                        $changes[] = ['action' => 'supprimer', 'note' => $note];
                    }
                    continue;
                }
                $changes[] = [
                    'action' => 'enregistrer',
                    'note' => $note ?? (new Note())
                        ->setEleve($studentsById[(int) $studentId])
                        ->setEvaluation($evaluation),
                    'cote' => $raw,
                ];
            }
        }

        if ($changes === []) {
            return $this->error('Aucune nouvelle cote ou modification à enregistrer.', [], 'Enregistrer les cotations', Response::HTTP_CONFLICT);
        }
        if (
            $action === 'soumettre'
            && count(array_filter($changes, static fn(array $change): bool => $change['action'] === 'enregistrer')) === 0
        ) {
            return $this->error('Aucune cote à soumettre. Les suppressions de brouillons doivent être enregistrées séparément.', [], 'Enregistrer les cotations', Response::HTTP_CONFLICT);
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

        return $this->success([
            'coursId' => $cours->getId(),
            'action' => $action,
            'mode' => $mode,
            'cotesTraitees' => count($changes),
        ], 'Enregistrer les cotations');
    }

    #[Route('/professeur/preparations', name: 'api_mobile_teacher_preparations', methods: ['GET'])]
    public function teacherPreparations(Request $request, PreparationCoursRepository $preparations): JsonResponse
    {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Préparations professeur', $auth['code']);
        }
        $professeur = $auth['user']->getProfesseur();
        if (!$this->userHasAnyRole($auth['user'], ['ROLE_PROFESSEUR']) || !$professeur instanceof Professeur) {
            return $this->error('Cet espace est réservé aux comptes professeurs.', [], 'Préparations professeur', Response::HTTP_FORBIDDEN);
        }

        return $this->success([
            'preparations' => array_map(
                fn(PreparationCours $preparation): array => $this->preparationPayload($preparation),
                $preparations->findForProfesseur($professeur)
            ),
        ], 'Préparations professeur');
    }

    #[Route('/professeur/preparations', name: 'api_mobile_teacher_preparation_create', methods: ['POST'])]
    public function createPreparation(
        Request $request,
        CoursRepository $courses,
        PreparationCoursRepository $preparations,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Créer une préparation', $auth['code']);
        }
        $professeur = $auth['user']->getProfesseur();
        if (!$this->userHasAnyRole($auth['user'], ['ROLE_PROFESSEUR']) || !$professeur instanceof Professeur) {
            return $this->error('Cet espace est réservé aux comptes professeurs.', [], 'Créer une préparation', Response::HTTP_FORBIDDEN);
        }

        $data = $this->getJsonData($request);
        $coursId = filter_var($data['coursId'] ?? null, FILTER_VALIDATE_INT);
        $cours = $coursId ? $courses->find($coursId) : null;
        if (!$cours instanceof Cours || $cours->getProfesseur()?->getId() !== $professeur->getId() || $cours->isDeleted()) {
            return $this->error('Le cours est invalide ou ne vous est pas attribué.', [], 'Créer une préparation', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $preparation = (new PreparationCours())
            ->setProfesseur($professeur)
            ->setCours($cours)
            ->setNumero($preparations->prochainNumero($professeur, $cours));
        $inputErrors = $this->applyPreparationData($preparation, $data);
        $preparation->setCreatedBy($auth['user']);

        $errors = [...$inputErrors, ...$this->validateObject($preparation)];
        if ($errors !== []) {
            return $this->error('Les données de la fiche sont invalides.', $errors, 'Créer une préparation', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->persist($preparation);
        $entityManager->flush();

        return $this->success($this->preparationPayload($preparation), 'Créer une préparation', Response::HTTP_CREATED);
    }

    #[Route('/professeur/preparations/{id}', name: 'api_mobile_teacher_preparation_update', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function updatePreparation(
        Request $request,
        PreparationCours $preparation,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Modifier une préparation', $auth['code']);
        }
        if (
            !$this->userHasAnyRole($auth['user'], ['ROLE_PROFESSEUR'])
            || $auth['user']->getProfesseur()?->getId() !== $preparation->getProfesseur()?->getId()
            || !in_array($preparation->getStatut(), [PreparationCours::BROUILLON, PreparationCours::A_CORRIGER], true)
        ) {
            return $this->error('Cette préparation ne vous appartient pas ou ne peut plus être modifiée.', [], 'Modifier une préparation', Response::HTTP_FORBIDDEN);
        }

        $inputErrors = $this->applyPreparationData($preparation, $this->getJsonData($request));
        $errors = [...$inputErrors, ...$this->validateObject($preparation)];
        if ($errors !== []) {
            return $this->error('Les données de la fiche sont invalides.', $errors, 'Modifier une préparation', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($preparation->getStatut() === PreparationCours::A_CORRIGER) {
            $preparation->setStatut(PreparationCours::BROUILLON)
                ->setTraiteeLe(null)
                ->setTraiteePar(null);
        }
        $entityManager->flush();

        return $this->success($this->preparationPayload($preparation), 'Modifier une préparation');
    }

    #[Route('/professeur/preparations/{id}/soumettre', name: 'api_mobile_teacher_preparation_submit', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function submitPreparation(
        Request $request,
        PreparationCours $preparation,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Soumettre une préparation', $auth['code']);
        }
        if (
            !$this->userHasAnyRole($auth['user'], ['ROLE_PROFESSEUR'])
            || $auth['user']->getProfesseur()?->getId() !== $preparation->getProfesseur()?->getId()
            || !in_array($preparation->getStatut(), [PreparationCours::BROUILLON, PreparationCours::A_CORRIGER], true)
        ) {
            return $this->error('Cette préparation ne peut pas être soumise par ce compte.', [], 'Soumettre une préparation', Response::HTTP_FORBIDDEN);
        }
        $errors = $this->validateObject($preparation);
        if ($errors !== []) {
            return $this->error('Complétez la fiche avant de la soumettre.', $errors, 'Soumettre une préparation', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $preparation->setStatut(PreparationCours::SOUMISE)
            ->setSoumiseLe(new \DateTimeImmutable())
            ->setCommentaireDirection(null)
            ->setTraiteeLe(null)
            ->setTraiteePar(null);
        $entityManager->flush();

        return $this->success($this->preparationPayload($preparation), 'Soumettre une préparation');
    }

    #[Route('/admin/notes', name: 'api_mobile_admin_notes', methods: ['GET'])]
    public function adminNotes(Request $request): JsonResponse
    {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Notes soumises', $auth['code']);
        }
        if (!$this->isAdministrator($auth['user'])) {
            return $this->error('Accès réservé à la direction.', [], 'Notes soumises', Response::HTTP_FORBIDDEN);
        }

        $notes = $this->em->getRepository(Note::class)->createQueryBuilder('note')
            ->addSelect('evaluation', 'cours', 'classe', 'eleve')
            ->innerJoin('note.evaluation', 'evaluation')
            ->innerJoin('evaluation.cours', 'cours')
            ->innerJoin('cours.classe', 'classe')
            ->innerJoin('note.eleve', 'eleve')
            ->andWhere('note.statut = :statut')
            ->setParameter('statut', 'soumise')
            ->orderBy('evaluation.id', 'DESC')
            ->addOrderBy('eleve.nom', 'ASC')
            ->setMaxResults(min(200, max(1, $request->query->getInt('limit', 100))))
            ->getQuery()
            ->getResult();

        return $this->success([
            'notesSoumises' => array_map(static fn(Note $note): array => [
                'id' => $note->getId(),
                'cote' => $note->getCote(),
                'observation' => $note->getObservation(),
                'eleve' => [
                    'id' => $note->getEleve()?->getId(),
                    'nom' => trim(implode(' ', array_filter([
                        $note->getEleve()?->getNom(),
                        $note->getEleve()?->getPostnom(),
                        $note->getEleve()?->getPrenom(),
                    ]))),
                ],
                'cours' => [
                    'id' => $note->getEvaluation()?->getCours()?->getId(),
                    'nom' => $note->getEvaluation()?->getCours()?->getNom(),
                    'classe' => $note->getEvaluation()?->getCours()?->getClasse()?->getNom(),
                    'ecole' => $note->getEvaluation()?->getCours()?->getClasse()?->getEcole()?->getNom(),
                ],
                'evaluation' => [
                    'id' => $note->getEvaluation()?->getId(),
                    'nom' => $note->getEvaluation()?->getNom(),
                    'bareme' => $note->getEvaluation()?->getBareme(),
                ],
                'dateSoumission' => $note->getDateSoumission()?->format(DATE_ATOM),
            ], $notes),
        ], 'Notes soumises');
    }

    #[Route('/admin/evaluations/{id}/notes/{action}', name: 'api_mobile_admin_review_notes', methods: ['POST'], requirements: ['id' => '\d+', 'action' => 'valider|retourner'])]
    public function reviewNotes(
        Request $request,
        Evaluation $evaluation,
        string $action,
        NoteRepository $notes,
        BulletinAnnuelRepository $bulletins,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Traitement des cotations', $auth['code']);
        }
        if (!$this->isAdministrator($auth['user'])) {
            return $this->error('Accès réservé à la direction.', [], 'Traitement des cotations', Response::HTTP_FORBIDDEN);
        }

        $submittedNotes = $notes->findBy(['evaluation' => $evaluation, 'statut' => 'soumise']);
        if ($submittedNotes === []) {
            return $this->error('Aucune note soumise pour cette évaluation.', [], 'Traitement des cotations', Response::HTTP_CONFLICT);
        }
        foreach ($submittedNotes as $note) {
            $note->setStatut($action === 'valider' ? 'validee' : 'brouillon');
            if ($action === 'retourner') {
                $note->setDateSoumission(null);
            }
        }
        $entityManager->flush();

        $classe = $evaluation->getCours()?->getClasse();
        $annee = $evaluation->getConfigurationBulletin()?->getAnneeScolaire();
        if ($classe instanceof Classe && $annee instanceof AnneeScolaire) {
            $bulletins->deleteForEvaluationClassAndYear($classe, $annee);
            $entityManager->flush();
        }

        return $this->success([
            'evaluationId' => $evaluation->getId(),
            'action' => $action,
            'notesTraitees' => count($submittedNotes),
        ], 'Traitement des cotations');
    }

    #[Route('/admin/preparations', name: 'api_mobile_admin_preparations', methods: ['GET'])]
    public function adminPreparations(Request $request, PreparationCoursRepository $preparations): JsonResponse
    {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Préparations à valider', $auth['code']);
        }
        if (!$this->isAdministrator($auth['user'])) {
            return $this->error('Accès réservé à la direction.', [], 'Préparations à valider', Response::HTTP_FORBIDDEN);
        }

        return $this->success([
            'preparations' => array_map(
                fn(PreparationCours $preparation): array => $this->preparationPayload($preparation),
                $preparations->findSoumises()
            ),
        ], 'Préparations à valider');
    }

    #[Route('/admin/preparations/{id}/{decision}', name: 'api_mobile_admin_review_preparation', methods: ['POST'], requirements: ['id' => '\d+', 'decision' => 'valider|corriger'])]
    public function reviewPreparation(
        Request $request,
        PreparationCours $preparation,
        string $decision,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Traitement d’une préparation', $auth['code']);
        }
        if (!$this->isAdministrator($auth['user'])) {
            return $this->error('Accès réservé à la direction.', [], 'Traitement d’une préparation', Response::HTTP_FORBIDDEN);
        }
        if ($preparation->getStatut() !== PreparationCours::SOUMISE) {
            return $this->error('Cette préparation n’attend pas une décision.', [], 'Traitement d’une préparation', Response::HTTP_CONFLICT);
        }
        $data = $this->getJsonData($request);
        $commentaire = trim(strip_tags((string) ($data['commentaire'] ?? '')));
        if ($decision === 'corriger' && $commentaire === '') {
            return $this->error('Un commentaire est obligatoire pour demander une correction.', [], 'Traitement d’une préparation', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $preparation->setStatut($decision === 'valider' ? PreparationCours::VALIDEE : PreparationCours::A_CORRIGER)
            ->setCommentaireDirection($commentaire !== '' ? $commentaire : null)
            ->setTraiteeLe(new \DateTimeImmutable())
            ->setTraiteePar($auth['user']);
        $entityManager->flush();

        return $this->success($this->preparationPayload($preparation), 'Traitement d’une préparation');
    }

    #[Route('/admin/utilisateurs', name: 'api_mobile_admin_users', methods: ['GET'])]
    public function adminUsers(Request $request, UserRepository $users): JsonResponse
    {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Liste des utilisateurs', $auth['code']);
        }
        if (!$this->isAdministrator($auth['user'])) {
            return $this->error('Accès réservé à la direction.', [], 'Liste des utilisateurs', Response::HTTP_FORBIDDEN);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 25)));
        $queryBuilder = $users->createQueryBuilder('user')
            ->andWhere('user.deleted = :deleted')
            ->setParameter('deleted', false)
            ->orderBy('user.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $items = $queryBuilder->getQuery()->getResult();
        $total = $users->count(['deleted' => false]);

        return $this->success([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'utilisateurs' => array_map(static fn(User $user): array => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'nom' => $user->getLastname(),
                'prenom' => $user->getFirstname(),
                'roles' => $user->getRoles(),
                'actif' => $user->isEnabled(),
                'profil' => $user->getEleve() ? 'eleve' : ($user->getProfesseur() ? 'professeur' : ($user->getParent() ? 'parent' : 'personnel')),
            ], $items),
        ], 'Liste des utilisateurs');
    }

    #[Route('/admin/bulletins/calculer', name: 'api_mobile_admin_bulletins_calculate', methods: ['POST'])]
    public function calculateBulletins(
        Request $request,
        EcoleRepository $ecoles,
        AnneeScolaireRepository $annees,
        ClasseRepository $classes,
        SectionRepository $sections,
        OptionRepository $options,
        BulletinAnnuelCalculator $calculator
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Calcul des bulletins', $auth['code']);
        }
        if (!$this->isAdministrator($auth['user'])) {
            return $this->error('Accès réservé à la direction.', [], 'Calcul des bulletins', Response::HTTP_FORBIDDEN);
        }

        $data = $this->getJsonData($request);
        $ecole = $ecoles->find(filter_var($data['ecoleId'] ?? null, FILTER_VALIDATE_INT) ?: 0);
        $annee = $annees->find(filter_var($data['anneeScolaireId'] ?? null, FILTER_VALIDATE_INT) ?: 0);
        if (!$ecole instanceof Ecole || !$annee instanceof AnneeScolaire || $annee->getEcole()?->getId() !== $ecole->getId()) {
            return $this->error('L’école ou l’année scolaire est invalide, ou elles ne correspondent pas.', [], 'Calcul des bulletins', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $section = $this->optionalEntity($sections, $data['sectionId'] ?? null, \App\Entity\Section::class);
        $classe = $this->optionalEntity($classes, $data['classeId'] ?? null, Classe::class);
        $option = $this->optionalEntity($options, $data['optionId'] ?? null, \App\Entity\Option::class);
        if (
            (($data['sectionId'] ?? null) !== null && !$section instanceof \App\Entity\Section)
            || (($data['classeId'] ?? null) !== null && !$classe instanceof Classe)
            || (($data['optionId'] ?? null) !== null && !$option instanceof \App\Entity\Option)
            || ($section instanceof \App\Entity\Section && $section->getEcole()?->getId() !== $ecole->getId())
            || ($classe instanceof Classe && (
                $classe->getEcole()?->getId() !== $ecole->getId()
                || ($section instanceof \App\Entity\Section && $classe->getSection()?->getId() !== $section->getId())
            ))
            || ($option instanceof \App\Entity\Option && (
                !$classe instanceof Classe
                || $option->getClasse()?->getId() !== $classe->getId()
            ))
        ) {
            return $this->error('Les filtres classe, section ou option ne correspondent pas à cette école.', [], 'Calcul des bulletins', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $calculator->calculate($ecole, $annee, $section, $classe, $option);

        return $this->success($result, 'Calcul des bulletins');
    }

    #[Route('/admin/bulletins', name: 'api_mobile_admin_bulletins', methods: ['GET'])]
    public function adminBulletins(
        Request $request,
        EcoleRepository $ecoles,
        AnneeScolaireRepository $annees,
        BulletinAnnuelRepository $bulletins,
        ClasseRepository $classes,
        SectionRepository $sections,
        OptionRepository $options
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status']) {
            return $this->error($auth['message'], [], 'Bulletins annuels', $auth['code']);
        }
        if (!$this->isAdministrator($auth['user'])) {
            return $this->error('Accès réservé à la direction.', [], 'Bulletins annuels', Response::HTTP_FORBIDDEN);
        }

        $ecole = $ecoles->find($request->query->getInt('ecole'));
        $annee = $annees->find($request->query->getInt('anneeScolaire'));
        if (!$ecole instanceof Ecole || !$annee instanceof AnneeScolaire || $annee->getEcole()?->getId() !== $ecole->getId()) {
            return $this->error('Fournissez une école et une année scolaire correspondantes.', [], 'Bulletins annuels', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $classeId = $request->query->getInt('classe');
        $sectionId = $request->query->getInt('section');
        $optionId = $request->query->getInt('option');
        $classe = $classeId > 0 ? $classes->find($classeId) : null;
        $section = $sectionId > 0 ? $sections->find($sectionId) : null;
        $option = $optionId > 0 ? $options->find($optionId) : null;
        if (
            ($request->query->has('classe') && ($classeId < 1 || !$classe instanceof Classe || $classe->getEcole()?->getId() !== $ecole->getId()))
            || ($request->query->has('section') && ($sectionId < 1 || !$section instanceof \App\Entity\Section || $section->getEcole()?->getId() !== $ecole->getId()))
            || ($request->query->has('option') && ($optionId < 1 || !$option instanceof \App\Entity\Option || !$classe instanceof Classe || $option->getClasse()?->getId() !== $classe->getId()))
        ) {
            return $this->error('Les filtres ne correspondent pas à cette école.', [], 'Bulletins annuels', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $items = $bulletins->findForFilters($ecole, $annee, $section, $classe, $option);

        return $this->success([
            'bulletins' => array_map(static fn(BulletinAnnuel $bulletin): array => [
                'id' => $bulletin->getId(),
                'eleve' => [
                    'id' => $bulletin->getEleve()?->getId(),
                    'matricule' => $bulletin->getEleve()?->getMatricule(),
                    'nom' => trim(implode(' ', array_filter([
                        $bulletin->getEleve()?->getNom(),
                        $bulletin->getEleve()?->getPostnom(),
                        $bulletin->getEleve()?->getPrenom(),
                    ]))),
                ],
                'classe' => $bulletin->getClasse()?->getNom(),
                'option' => $bulletin->getOption()?->getNom(),
                'anneeScolaire' => $bulletin->getAnneeScolaire()?->getLibelle(),
                'totalCotes' => $bulletin->getTotalCotes(),
                'totalBaremes' => $bulletin->getTotalBaremes(),
                'pourcentage' => $bulletin->getPourcentage(),
                'decision' => $bulletin->getDecision(),
                'details' => $bulletin->getDetails(),
                'calculeLe' => $bulletin->getCalculeLe()->format(DATE_ATOM),
            ], $items),
        ], 'Bulletins annuels');
    }

    private function applyPreparationData(PreparationCours $preparation, array $data): array
    {
        $errors = [];
        $fields = [
            'jour' => 'setJour',
            'sujet' => 'setSujet',
            'reference' => 'setReference',
            'objectifGeneral' => 'setObjectifGeneral',
            'objectifsSpecifiques' => 'setObjectifsSpecifiques',
            'matiere' => 'setMatiere',
            'methode' => 'setMethode',
            'materielDidactique' => 'setMaterielDidactique',
            'activitesInitialesEnseignant' => 'setActivitesInitialesEnseignant',
            'activitesInitialesEleves' => 'setActivitesInitialesEleves',
            'activitesPrincipalesEnseignant' => 'setActivitesPrincipalesEnseignant',
            'activitesPrincipalesEleves' => 'setActivitesPrincipalesEleves',
            'activitesFinalesEnseignant' => 'setActivitesFinalesEnseignant',
            'activitesFinalesEleves' => 'setActivitesFinalesEleves',
            'evaluation' => 'setEvaluation',
            'devoir' => 'setDevoir',
        ];
        foreach ($fields as $key => $setter) {
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
                if ($value !== null && !is_string($value)) {
                    $errors[] = sprintf('Le champ "%s" doit être une chaîne de caractères.', $key);
                    continue;
                }
                if ($value === null && in_array($key, ['jour', 'sujet'], true)) {
                    $errors[] = sprintf('Le champ "%s" est obligatoire.', $key);
                    continue;
                }
                if ($key === 'jour' && !in_array($value, ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'], true)) {
                    $errors[] = 'Le jour doit être un jour de la semaine valide.';
                    continue;
                }
                $preparation->$setter($value);
            }
        }

        foreach (['dateCours' => 'setDateCours', 'heureDebut' => 'setHeureDebut', 'heureFin' => 'setHeureFin'] as $key => $setter) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            if ($data[$key] === null || $data[$key] === '') {
                if ($key !== 'dateCours') {
                    $preparation->$setter(null);
                } else {
                    $errors[] = 'Le champ "dateCours" est obligatoire et doit utiliser le format AAAA-MM-JJ.';
                }
                continue;
            }
            if (!is_string($data[$key])) {
                $errors[] = sprintf('Le champ "%s" doit être une chaîne au format %s.', $key, $key === 'dateCours' ? 'AAAA-MM-JJ' : 'HH:MM');
                continue;
            }
            $format = $key === 'dateCours' ? '!Y-m-d' : '!H:i';
            $parsed = \DateTime::createFromFormat($format, $data[$key]);
            $dateErrors = \DateTime::getLastErrors();
            if ($parsed !== false && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))) {
                $preparation->$setter($parsed);
            } else {
                $errors[] = sprintf('Le champ "%s" doit respecter le format %s.', $key, $key === 'dateCours' ? 'AAAA-MM-JJ' : 'HH:MM');
            }
        }

        return $errors;
    }

    private function preparationPayload(PreparationCours $preparation): array
    {
        return [
            'id' => $preparation->getId(),
            'numero' => $preparation->getNumero(),
            'statut' => $preparation->getStatut(),
            'dateCours' => $preparation->getDateCours()->format('Y-m-d'),
            'jour' => $preparation->getJour(),
            'heureDebut' => $preparation->getHeureDebut()?->format('H:i'),
            'heureFin' => $preparation->getHeureFin()?->format('H:i'),
            'sujet' => $preparation->getSujet(),
            'reference' => $preparation->getReference(),
            'objectifGeneral' => $preparation->getObjectifGeneral(),
            'objectifsSpecifiques' => $preparation->getObjectifsSpecifiques(),
            'matiere' => $preparation->getMatiere(),
            'methode' => $preparation->getMethode(),
            'materielDidactique' => $preparation->getMaterielDidactique(),
            'activitesInitialesEnseignant' => $preparation->getActivitesInitialesEnseignant(),
            'activitesInitialesEleves' => $preparation->getActivitesInitialesEleves(),
            'activitesPrincipalesEnseignant' => $preparation->getActivitesPrincipalesEnseignant(),
            'activitesPrincipalesEleves' => $preparation->getActivitesPrincipalesEleves(),
            'activitesFinalesEnseignant' => $preparation->getActivitesFinalesEnseignant(),
            'activitesFinalesEleves' => $preparation->getActivitesFinalesEleves(),
            'evaluation' => $preparation->getEvaluation(),
            'devoir' => $preparation->getDevoir(),
            'commentaireDirection' => $preparation->getCommentaireDirection(),
            'cours' => [
                'id' => $preparation->getCours()?->getId(),
                'nom' => $preparation->getCours()?->getNom(),
                'classe' => $preparation->getCours()?->getClasse()?->getNom(),
                'ecole' => $preparation->getCours()?->getClasse()?->getEcole()?->getNom(),
            ],
            'professeur' => [
                'id' => $preparation->getProfesseur()?->getId(),
                'nom' => trim(implode(' ', array_filter([
                    $preparation->getProfesseur()?->getNom(),
                    $preparation->getProfesseur()?->getPostnom(),
                    $preparation->getProfesseur()?->getPrenom(),
                ]))),
            ],
            'soumiseLe' => $preparation->getSoumiseLe()?->format(DATE_ATOM),
            'traiteeLe' => $preparation->getTraiteeLe()?->format(DATE_ATOM),
        ];
    }

    private function parentPayload(Parents $parent): array
    {
        return [
            'id' => $parent->getId(),
            'nom' => $parent->getNom(),
            'postnom' => $parent->getPostnom(),
            'prenom' => $parent->getPrenom(),
            'telephone' => $parent->getTelephone(),
            'email' => $parent->getEmail(),
            'adresse' => $parent->getAdresse(),
            'profession' => $parent->getProfession(),
            'type' => $parent->getType(),
        ];
    }

    private function childPayload(Eleve $eleve): array
    {
        return [
            'id' => $eleve->getId(),
            'matricule' => $eleve->getMatricule(),
            'nom' => $eleve->getNom(),
            'postnom' => $eleve->getPostnom(),
            'prenom' => $eleve->getPrenom(),
            'sexe' => $eleve->getSexe(),
            'dateNaissance' => $eleve->getDateNaissance()?->format('Y-m-d'),
            'lieuNaissance' => $eleve->getLieuNaissance(),
            'telephone' => $eleve->getPhone(),
            'email' => $eleve->getEmail(),
            'adresse' => $eleve->getAdresse(),
            'dateInscription' => $eleve->getDateInscription()?->format('Y-m-d'),
            'actif' => $eleve->isStatut(),
            'classe' => [
                'id' => $eleve->getClasse()?->getId(),
                'nom' => $eleve->getClasse()?->getNom(),
                'section' => $eleve->getClasse()?->getSection()?->getNom(),
            ],
            'option' => $eleve->getOption()?->getNom(),
            'ecole' => [
                'id' => $eleve->getEcole()?->getId(),
                'nom' => $eleve->getEcole()?->getNom(),
                'adresse' => $eleve->getEcole()?->getAdresse(),
                'telephone' => $eleve->getEcole()?->getPhone(),
                'email' => $eleve->getEcole()?->getEmail(),
            ],
        ];
    }

    private function validatedNotesForStudent(Eleve $eleve): array
    {
        $validated = array_values(array_filter(
            $eleve->getNotes()->toArray(),
            static fn(Note $note): bool => $note->getStatut() === 'validee'
        ));

        return array_map(static fn(Note $note): array => [
            'id' => $note->getId(),
            'cote' => $note->getCote(),
            'observation' => $note->getObservation(),
            'cours' => $note->getEvaluation()?->getCours()?->getNom(),
            'evaluation' => $note->getEvaluation()?->getNom(),
            'bareme' => $note->getEvaluation()?->getBareme(),
        ], $validated);
    }

    private function scheduleForStudent(Eleve $eleve, CreneauHoraireRepository $slots): array
    {
        $classe = $eleve->getClasse();
        $ecole = $eleve->getEcole();
        if (!$classe instanceof Classe || !$ecole instanceof Ecole) {
            return [];
        }

        $queryBuilder = $slots->createQueryBuilder('slot')
            ->addSelect('horaire', 'jour', 'heure', 'cours', 'professeur')
            ->innerJoin('slot.horaire', 'horaire')
            ->innerJoin('slot.jour', 'jour')
            ->innerJoin('slot.heure', 'heure')
            ->leftJoin('slot.cours', 'cours')
            ->leftJoin('slot.professeur', 'professeur')
            ->andWhere('horaire.classe = :classe')
            ->andWhere('horaire.ecole = :ecole')
            ->setParameter('classe', $classe)
            ->setParameter('ecole', $ecole);
        if ($eleve->getOption() !== null) {
            $queryBuilder->andWhere('horaire.option = :option')->setParameter('option', $eleve->getOption());
        } else {
            $queryBuilder->andWhere('horaire.option IS NULL');
        }
        $items = $queryBuilder->orderBy('jour.ordre', 'ASC')->addOrderBy('heure.ordre', 'ASC')->getQuery()->getResult();

        return array_map(static fn($slot): array => [
            'jour' => $slot->getJour()?->getNom(),
            'heureDebut' => $slot->getHeure()?->getHeureDebut()?->format('H:i'),
            'heureFin' => $slot->getHeure()?->getHeureFin()?->format('H:i'),
            'cours' => $slot->getCours()?->getNom(),
            'professeur' => $slot->getProfesseur() ? trim(implode(' ', array_filter([
                $slot->getProfesseur()->getNom(),
                $slot->getProfesseur()->getPostnom(),
                $slot->getProfesseur()->getPrenom(),
            ]))) : null,
        ], $items);
    }

    private function optionalEntity(object $repository, mixed $id, string $class): ?object
    {
        if ($id === null || $id === '') {
            return null;
        }
        $validId = filter_var($id, FILTER_VALIDATE_INT);
        if ($validId === false || $validId < 1) {
            return null;
        }
        $entity = $repository->find($validId);

        return $entity instanceof $class ? $entity : null;
    }

    private function isAdministrator(User $user): bool
    {
        return (bool) array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $user->getRoles());
    }
}
