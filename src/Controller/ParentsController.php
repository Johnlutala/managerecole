<?php

namespace App\Controller;

use App\Entity\Parents;
use App\Entity\User;
use App\Form\ParentsType;
use App\Repository\ParentsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/parents', name: 'app_parents_',)]
final class ParentsController extends AbstractController
{
    #[Route(name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, ParentsRepository $parentsRepository, EntityManagerInterface $entityManager): Response
    {
        $parent = new Parents();
        $form = $this->createForm(ParentsType::class, $parent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $parent->setCreatedBy($this->getUser());
            }
            $entityManager->persist($parent);
            $entityManager->flush();
            $this->addFlash('success', 'Parent créé avec succès.');

            return $this->redirectToRoute('app_parents_index');
        }

        return $this->render('parents/index.html.twig', [
            'parents' => $parentsRepository->findBy(['deleted' => false]),
            'parent' => $parent,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parent = new Parents();
        $form = $this->createForm(ParentsType::class, $parent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $parent->setCreatedBy($this->getUser());
            }
            $entityManager->persist($parent);
            $entityManager->flush();

            $this->addFlash('success', 'Parent créé avec succès.');

            return $this->redirectToRoute('app_parents_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('parents/new.html.twig', [
            'parent' => $parent,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Parents $parent): Response
    {
        return $this->render('parents/show.html.twig', [
            'parent' => $parent,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Parents $parent, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParentsType::class, $parent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Parent mis à jour avec succès.');

            return $this->redirectToRoute('app_parents_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('parents/edit.html.twig', [
            'parent' => $parent,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Parents $parent, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $parent->getId(), $request->getPayload()->getString('_token'))) {
            $parent->setDeleted(true);
            $parent->setEnabled(false);
            $entityManager->flush();
            $this->addFlash('success', 'Parent supprimé avec succès.');
        }

        return $this->redirectToRoute('app_parents_index', [], Response::HTTP_SEE_OTHER);
    }
}
