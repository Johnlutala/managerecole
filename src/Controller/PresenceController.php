<?php

namespace App\Controller;

use App\Entity\Presence;
use App\Entity\User;
use App\Form\PresenceType;
use App\Repository\PresenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/presence')]
final class PresenceController extends AbstractController
{
    #[Route(name: 'app_presence_index', methods: ['GET', 'POST'])]
    public function index(Request $request, PresenceRepository $presenceRepository, EntityManagerInterface $entityManager): Response
    {
        $presence = new Presence();
        $form = $this->createForm(PresenceType::class, $presence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $presence->setCreatedBy($this->getUser());
            }
            $entityManager->persist($presence);
            $entityManager->flush();
            $this->addFlash('success', 'Présence créée avec succès.');

            return $this->redirectToRoute('app_presence_index');
        }

        return $this->render('presence/index.html.twig', [
            'presences' => $presenceRepository->findBy(['deleted' => false]),
            'presence' => $presence,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_presence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $presence = new Presence();
        $form = $this->createForm(PresenceType::class, $presence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $presence->setCreatedBy($this->getUser());
            }
            $entityManager->persist($presence);
            $entityManager->flush();

            $this->addFlash('success', 'Présence créée avec succès.');

            return $this->redirectToRoute('app_presence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('presence/new.html.twig', [
            'presence' => $presence,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_presence_show', methods: ['GET'])]
    public function show(Presence $presence): Response
    {
        return $this->render('presence/show.html.twig', [
            'presence' => $presence,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_presence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Presence $presence, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PresenceType::class, $presence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Présence mise à jour avec succès.');

            return $this->redirectToRoute('app_presence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('presence/edit.html.twig', [
            'presence' => $presence,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_presence_delete', methods: ['POST'])]
    public function delete(Request $request, Presence $presence, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $presence->getId(), $request->getPayload()->getString('_token'))) {
            $presence->setDeleted(true);
            $presence->setEnabled(false);
            $entityManager->flush();
            $this->addFlash('success', 'Présence supprimée avec succès.');
        }

        return $this->redirectToRoute('app_presence_index', [], Response::HTTP_SEE_OTHER);
    }
}
