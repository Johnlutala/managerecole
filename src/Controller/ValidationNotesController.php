<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/administration/notes', name: 'app_validation_notes_')]
#[IsGranted('ROLE_ADMIN')]
final class ValidationNotesController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(NoteRepository $notes): Response
    {
        return $this->render('validation_notes/index.html.twig', [
            'evaluations' => $notes->findSubmittedEvaluationSummaries(),
        ]);
    }

    #[Route('/evaluation/{id}/traiter', name: 'traiter', methods: ['POST'])]
    public function traiter(
        Request $request,
        Evaluation $evaluation,
        NoteRepository $notes,
        EntityManagerInterface $entityManager
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

        foreach ($notesSoumises as $note) {
            $note->setStatut($action === 'valider' ? 'validee' : 'brouillon');
        }
        $entityManager->flush();

        $this->addFlash(
            'success',
            $action === 'valider'
                ? 'Les notes soumises ont été validées.'
                : 'Les notes ont été retournées au professeur pour correction.'
        );

        return $this->redirectToRoute('app_validation_notes_index');
    }
}
