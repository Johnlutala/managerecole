<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Evaluation;
use App\Entity\Option;
use App\Entity\Section;
use App\Entity\User;
use App\Entity\MessageProfesseur;
use App\Repository\AutorisationCotationRepository;
use App\Repository\BulletinAnnuelRepository;
use App\Repository\ClasseRepository;
use App\Repository\ConfigurationFicheCotationRepository;
use App\Repository\CoursRepository;
use App\Repository\EcoleRepository;
use App\Repository\NoteRepository;
use App\Repository\OptionRepository;
use App\Repository\SectionRepository;
use App\Service\CotationStructure;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/administration/notes', name: 'app_validation_notes_')]
#[IsGranted('ROLE_ADMIN')]
final class ValidationNotesController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        NoteRepository $notes,
        EcoleRepository $ecoles,
        SectionRepository $sections,
        ClasseRepository $classes,
        OptionRepository $options,
        CoursRepository $cours,
        ConfigurationFicheCotationRepository $ficheConfigurations,
        AutorisationCotationRepository $autorisationRepository,
        CotationStructure $cotationStructure
    ): Response {
        $filters = $this->filtres($request, $ecoles, $sections, $classes, $options);
        $status = $request->query->get('statut');
        if (!in_array($status, ['', 'soumise', 'validee', 'non_soumise'], true)) {
            $status = '';
        }

        $schoolStats = [];
        $submittedCourseIds = array_fill_keys($notes->findCoursesWithSubmittedNotes(), true);
        $schools = array_filter(
            $ecoles->findBy([], ['nom' => 'ASC']),
            static fn (Ecole $ecole): bool => !$ecole->isDeleted()
        );
        foreach ($schools as $ecole) {
            $schoolCourses = array_values(array_filter(
                $cours->findForEcole($ecole),
                static fn ($course): bool => !$course->isDeleted()
                    && $course->getClasse() !== null
                    && !$course->getClasse()->isDeleted()
            ));
            $schoolClasses = array_filter(
                $classes->findBy(['ecole' => $ecole]),
                static fn (Classe $classe): bool => !$classe->isDeleted()
            );
            $submittedCount = 0;
            foreach ($schoolCourses as $course) {
                if (isset($submittedCourseIds[$course->getId()])) {
                    ++$submittedCount;
                }
            }
            $schoolStats[] = [
                'ecole' => $ecole,
                'classes' => count($schoolClasses),
                'soumises' => $submittedCount,
                'total' => count($schoolCourses),
            ];
        }

        $evaluations = $notes->findSubmittedEvaluationSummaries(
            $filters['ecole'],
            $filters['section'],
            $filters['classe'],
            $filters['option'],
            $status === '' ? null : $status
        );
        $authorizationsByStructure = [];
        $periodLabelsByStructure = [];
        $schoolStructures = [];
        $expectedEvaluations = [];
        foreach ($evaluations as $line) {
            $schoolId = $line['evaluation']->getCours()?->getClasse()?->getEcole()?->getId();
            if ($schoolId !== null && !array_key_exists($schoolId, $schoolStructures)) {
                $ficheConfiguration = $ficheConfigurations->findOneBy([
                    'ecole' => $line['evaluation']->getCours()?->getClasse()?->getEcole(),
                ]);
                $schoolStructures[$schoolId] = $ficheConfiguration?->getStructure();
            }
            $structure = $schoolId === null ? null : $schoolStructures[$schoolId];
            if ($structure === null) {
                continue;
            }
            $authorizationsByStructure[$structure] ??= $autorisationRepository->autorisationsPourStructure($structure);
            if (!isset($periodLabelsByStructure[$structure])) {
                foreach ($cotationStructure->groupes($structure) as $group) {
                    foreach ($group['periodes'] as $period) {
                        $periodLabelsByStructure[$structure][$period['nom']] = $period['libelle'];
                    }
                }
            }
            $periodName = $line['evaluation']->getNom();
            if (!($authorizationsByStructure[$structure][$periodName] ?? false)) {
                continue;
            }

            $line['periode_attendue'] = $periodLabelsByStructure[$structure][$periodName] ?? $periodName;
            $expectedEvaluations[] = $line;
        }
        $evaluations = $expectedEvaluations;

        return $this->render('validation_notes/index.html.twig', [
            ...$filters,
            'ecole_selectionnee' => $filters['ecole'],
            'section_selectionnee' => $filters['section'],
            'classe_selectionnee' => $filters['classe'],
            'option_selectionnee' => $filters['option'],
            'statut_selectionne' => $status,
            'evaluations' => $evaluations,
            'ecoles_stats' => $schoolStats,
        ]);
    }

    #[Route('/suivi', name: 'suivi', methods: ['GET'])]
    public function suivi(
        Request $request,
        EcoleRepository $ecoles,
        SectionRepository $sections,
        ClasseRepository $classes,
        OptionRepository $options,
        CoursRepository $cours,
        NoteRepository $notes
    ): Response {
        $filters = $this->filtres($request, $ecoles, $sections, $classes, $options);
        $courseRows = [];
        $submittedCount = 0;
        $totalCount = 0;
        if ($filters['ecole'] !== null && $filters['classe'] !== null) {
            $submittedCourseIds = array_fill_keys($notes->findCoursesWithSubmittedNotes(), true);
            $classCourses = array_values(array_filter(
                $cours->findForEcole($filters['ecole'], $filters['classe']),
                static fn ($course): bool => !$course->isDeleted()
                    && $course->getClasse() !== null
                    && !$course->getClasse()->isDeleted()
                    && (
                        $filters['option'] === null
                        || $course->getOption()?->getId() === $filters['option']->getId()
                    )
            ));
            $totalCount = count($classCourses);
            foreach ($classCourses as $course) {
                $isSubmitted = isset($submittedCourseIds[$course->getId()]);
                if ($isSubmitted) {
                    ++$submittedCount;
                }
                if (
                    ($filters['statut'] === 'soumise' && !$isSubmitted)
                    || ($filters['statut'] === 'non_soumise' && $isSubmitted)
                ) {
                    continue;
                }
                $professeur = $course->getProfesseur();
                $courseRows[] = [
                    'cours' => $course,
                    'professeur' => $professeur,
                    'soumise' => $isSubmitted,
                    'email_disponible' => $professeur !== null
                        && filter_var($professeur->getEmail(), FILTER_VALIDATE_EMAIL) !== false,
                ];
            }
        }

        return $this->render('validation_notes/suivi.html.twig', [
            ...$filters,
            'cours_suivi' => $courseRows,
            'cours_soumises' => $submittedCount,
            'cours_total' => $totalCount,
        ]);
    }

    #[Route('/evaluation/{id}', name: 'evaluation_show', methods: ['GET'])]
    public function evaluationShow(Evaluation $evaluation): Response
    {
        $course = $evaluation->getCours();
        if ($course === null) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('app_professeur_notes_cours', [
            'id' => $course->getId(),
            'mode' => 'consultation',
        ]);
    }

    #[Route('/suivi/cours/{id}/message', name: 'message', methods: ['POST'])]
    public function envoyerMessage(
        Request $request,
        int $id,
        CoursRepository $cours,
        MailerInterface $mailer,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ): Response {
        $course = $cours->find($id);
        if (
            $course === null
            || $course->isDeleted()
            || $course->getClasse()?->getEcole() === null
            || $course->getClasse()?->isDeleted()
        ) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('message_cours_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $professeur = $course->getProfesseur();
        $subject = trim((string) $request->request->get('subject'));
        $message = trim((string) $request->request->get('message'));
        if (
            $professeur === null
            || filter_var($professeur->getEmail(), FILTER_VALIDATE_EMAIL) === false
            || $subject === ''
            || strlen($subject) > 180
            || $message === ''
            || strlen($message) > 10000
        ) {
            $this->addFlash('warning', 'Le professeur doit avoir une adresse e-mail valide et le message doit contenir un objet et un texte.');

            return $this->redirectToRoute('app_validation_notes_suivi', [
                'ecole' => $course->getClasse()->getEcole()->getId(),
                'classe' => $course->getClasse()->getId(),
                'option' => $course->getOption()?->getId(),
            ]);
        }

        $senderEmail = $_ENV['SENDER_EMAIL'] ?? '';
        if (filter_var($senderEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \LogicException('La configuration SENDER_EMAIL est absente ou invalide.');
        }
        $senderName = (string) ($_ENV['SENDER_NAME'] ?? 'Administration');
        $sender = $this->getUser();
        if (!$sender instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $internalMessage = (new MessageProfesseur())
            ->setProfesseur($professeur)
            ->setCours($course)
            ->setExpediteur($sender)
            ->setExpediteurNom(substr(
                trim(($sender->getFirstname() ?? '') . ' ' . ($sender->getLastname() ?? ''))
                    ?: $sender->getEmail(),
                0,
                120
            ))
            ->setExpediteurEmail($sender->getEmail())
            ->setSujet($subject)
            ->setContenu($message);
        $entityManager->persist($internalMessage);
        $entityManager->flush();
        try {
            $mailer->send(
                (new Email())
                    ->from(new Address($senderEmail, $senderName))
                    ->to($professeur->getEmail())
                    ->subject($subject)
                    ->text($message)
            );
        } catch (TransportExceptionInterface $exception) {
            $logger->error('Échec de l’envoi du message au professeur.', [
                'cours_id' => $course->getId(),
                'professeur_id' => $professeur->getId(),
                'error' => $exception->getMessage(),
            ]);
            $this->addFlash('warning', 'Le message est disponible dans la boîte du professeur, mais son e-mail n’a pas pu être envoyé.');

            return $this->redirectToRoute('app_validation_notes_suivi', [
                'ecole' => $course->getClasse()->getEcole()->getId(),
                'classe' => $course->getClasse()->getId(),
                'option' => $course->getOption()?->getId(),
            ]);
        }

        $this->addFlash('success', 'Le message a été envoyé par e-mail et ajouté à la boîte du professeur.');

        return $this->redirectToRoute('app_validation_notes_suivi', [
            'ecole' => $course->getClasse()->getEcole()->getId(),
            'classe' => $course->getClasse()->getId(),
            'option' => $course->getOption()?->getId(),
        ]);
    }

    #[Route('/evaluation/{id}/traiter', name: 'traiter', methods: ['POST'])]
    public function traiter(
        Request $request,
        Evaluation $evaluation,
        NoteRepository $notes,
        EntityManagerInterface $entityManager,
        BulletinAnnuelRepository $bulletins
    ): Response {
        if (!$this->isCsrfTokenValid('validation_notes_' . $evaluation->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $action = (string) $request->request->get('action');
        if (!in_array($action, ['valider', 'retourner'], true)) {
            throw $this->createAccessDeniedException();
        }

        $notesSoumises = $notes->findBy(['evaluation' => $evaluation, 'statut' => 'soumise']);
        if ($notesSoumises === []) {
            $this->addFlash('warning', 'Aucune note soumise à traiter pour cette évaluation.');

            return $this->redirectToRoute('app_validation_notes_index');
        }
        $configuration = $evaluation->getConfigurationBulletin();
        if ($action === 'valider' && $configuration === null) {
            throw new \LogicException('Cette évaluation ne possède aucune configuration de bulletin.');
        }

        foreach ($notesSoumises as $note) {
            $note->setStatut($action === 'valider' ? 'validee' : 'brouillon');
            if ($action === 'retourner') {
                $note->setDateSoumission(null);
            }
        }
        $entityManager->flush();
        $courseClass = $evaluation->getCours()?->getClasse();
        $schoolYear = $configuration?->getAnneeScolaire();
        if ($courseClass instanceof Classe && $schoolYear !== null) {
            $bulletins->deleteForEvaluationClassAndYear($courseClass, $schoolYear);
            $entityManager->flush();
        }

        if ($action === 'valider') {
            $this->addFlash('success', 'Les notes soumises ont été validées. Le calcul annuel se fait maintenant dans l’espace bulletin.');
        } else {
            $this->addFlash('success', 'Les notes ont été retournées au professeur pour correction. Les résultats annuels existants ont été invalidés.');
        }

        return $this->redirectToRoute('app_validation_notes_index');
    }

    /**
     * @return array{
     *     ecoles: Ecole[],
     *     ecole: ?Ecole,
     *     sections: Section[],
     *     section: ?Section,
     *     classes: Classe[],
     *     classe: ?Classe,
     *     options: Option[],
     *     option: ?Option,
     *     statut: string
     * }
     */
    private function filtres(
        Request $request,
        EcoleRepository $ecoles,
        SectionRepository $sections,
        ClasseRepository $classes,
        OptionRepository $options
    ): array {
        $schoolList = array_values(array_filter(
            $ecoles->findBy([], ['nom' => 'ASC']),
            static fn (Ecole $candidate): bool => !$candidate->isDeleted()
        ));
        $schoolId = $request->query->getInt('ecole');
        $school = $schoolId > 0 ? $ecoles->find($schoolId) : null;
        if ($school instanceof Ecole && $school->isDeleted()) {
            $school = null;
        }

        $sectionList = $school instanceof Ecole
            ? array_values(array_filter(
                $sections->findBy(['ecole' => $school], ['nom' => 'ASC']),
                static fn (Section $candidate): bool => !$candidate->isDeleted()
            ))
            : [];
        $sectionId = $request->query->getInt('section');
        $section = $sectionId > 0 ? $sections->find($sectionId) : null;
        if (
            !$section instanceof Section
            || $section->isDeleted()
            || $school === null
            || $section->getEcole()?->getId() !== $school->getId()
        ) {
            $section = null;
        }

        $classList = $school instanceof Ecole
            ? array_values(array_filter(
                $classes->findBy(['ecole' => $school], ['nom' => 'ASC']),
                static fn (Classe $candidate): bool => !$candidate->isDeleted()
            ))
            : [];
        if ($section !== null) {
            $classList = array_values(array_filter(
                $classList,
                static fn (Classe $candidate): bool => $candidate->getSection()?->getId() === $section->getId()
            ));
        }
        $classId = $request->query->getInt('classe');
        $class = $classId > 0 ? $classes->find($classId) : null;
        if (
            !$class instanceof Classe
            || $class->isDeleted()
            || $school === null
            || $class->getEcole()?->getId() !== $school->getId()
            || ($section !== null && $class->getSection()?->getId() !== $section->getId())
        ) {
            $class = null;
        }

        $optionList = $class instanceof Classe
            ? array_values(array_filter(
                $options->findBy(['classe' => $class], ['nom' => 'ASC']),
                static fn (Option $candidate): bool => !$candidate->isDeleted()
            ))
            : [];
        $optionId = $request->query->getInt('option');
        $option = $optionId > 0 ? $options->find($optionId) : null;
        if (
            !$option instanceof Option
            || $option->isDeleted()
            || $class === null
            || $option->getClasse()?->getId() !== $class->getId()
        ) {
            $option = null;
        }
        $status = (string) $request->query->get('statut', '');
        if (!in_array($status, ['', 'soumise', 'validee', 'non_soumise'], true)) {
            $status = '';
        }

        return [
            'ecoles' => $schoolList,
            'ecole' => $school,
            'sections' => $sectionList,
            'section' => $section,
            'classes' => $classList,
            'classe' => $class,
            'options' => $optionList,
            'option' => $option,
            'statut' => $status,
        ];
    }
}
