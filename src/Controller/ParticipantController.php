<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant', name: 'app_participant_')]
final class ParticipantController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response {

        $status = $request->query->get('status');

        // if ($this->isGranted('ROLE_ADMIN')) {

            // L'administrateur voit tous les participants
            if ($status === '0') {
                $participants = $participantRepository->findActive(null);
            } elseif ($status === '1') {
                $participants = $participantRepository->findBy([
                    'enabled' => false,
                    'deleted' => false,
                ], ['id' => 'ASC']);
            } else {
                $participants = $participantRepository->findBy([
                    'deleted' => false,
                ], ['id' => 'ASC']);
            }

        // } else {

        //     /** @var User $user */
        //     $user = $this->getUser();

        //     // L'utilisateur ne voit que les participants qu'il a créés
        //     if ($status === '0') {
        //         $participants = $participantRepository->findActiveByUser($user);
        //     } elseif ($status === '1') {
        //         $participants = $participantRepository->findDisabledByUser($user);
        //     } else {
        //         $participants = $participantRepository->findAllNotDeletedByUser($user);
        //     }
        // }
        $participant = new Participant();
        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Enregistre le créateur du participant
            $participant->setCreatedBy($this->getUser());

            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Participant créé avec succès.');

            return $this->redirectToRoute('app_participant_index');
        }

        return $this->render('participant/index.html.twig', [
            'participants' => $participants,
            'form' => $form->createView(),
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $participant = new Participant();
        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($participant);
            $entityManager->flush();

            return $this->redirectToRoute('app_participant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('participant/new.html.twig', [
            'participant' => $participant,
            'form' => $form,
        ]);
    }

    #[Route('/delete-selected', name: 'delete_selected', methods: ['POST'])]
    public function deleteSelected(
        Request $request,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('delete_selected', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $ids = $request->request->get('ids');

        if ($ids) {
            foreach (explode(',', $ids) as $id) {
                $participant = $participantRepository->find($id);

                if ($participant) {
                    $participant->setDeleted(true);
                    $entityManager->persist($participant);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Les participants ont été supprimés.');
        }

        return $this->redirectToRoute('app_participant_index');
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Participant $participant): Response
    {
        return $this->render('participant/show.html.twig', [
            'participant' => $participant,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Participant $participant, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_participant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('participant/edit.html.twig', [
            'participant' => $participant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Participant $participant, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $participant->getId(), $request->getPayload()->getString('_token'))) {
            $participant->setDeleted(true);
            $entityManager->persist($participant);
            $entityManager->flush();
            $this->addFlash('success', 'Participant supprimé avec succès.');
        }

        return $this->redirectToRoute('app_participant_index', [], Response::HTTP_SEE_OTHER);
    }
}
