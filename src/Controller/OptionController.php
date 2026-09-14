<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Option;
use App\Entity\User;
use App\Form\OptionType;
use App\Repository\OptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/option', name: 'app_option_')]
final class OptionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, OptionRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $option = new Option();
        $form = $this->createForm(OptionType::class, $option);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($this->getUser() instanceof User) {
                $option->setCreatedBy($this->getUser());
            }
            $entityManager->persist($option);
            $entityManager->flush();
            $this->addFlash('success', 'Option créé(e) avec succès.');
            return $this->redirectToRoute('app_option_index');
        }
        return $this->render('option/index.html.twig', ['option' => $option, 'options' => $repository->findAll(), 'form' => $form->createView()]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $option = new Option();
        $form = $this->createForm(OptionType::class, $option);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $option->setCreatedBy($this->getUser());
            }
            $entityManager->persist($option);
            $entityManager->flush();
            $this->addFlash('success', 'Option créé(e) avec succès.');
            return $this->redirectToRoute('app_option_index');
        }
        return $this->render('option/new.html.twig', ['option' => $option, 'form' => $form]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Option $option): Response
    {
        return $this->render('option/show.html.twig', ['option' => $option]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Option $option, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OptionType::class, $option);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Option mis(e) à jour avec succès.');
            return $this->redirectToRoute('app_option_index');
        }
        return $this->render('option/edit.html.twig', ['option' => $option, 'form' => $form]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Option $option, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $option->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($option);
            $entityManager->flush();
            $this->addFlash('success', 'Option supprimé(e) avec succès.');
        }
        return $this->redirectToRoute('app_option_index');
    }

    #[Route('/classes/{id}', name: 'classes', methods: ['GET'])]
    public function getClassesByEcole(

        Ecole $ecole,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $classes = $entityManager
            ->getRepository(Classe::class)
            ->findBy(
                ['ecole' => $ecole],
                ['nom' => 'ASC']
            );

        $data = [];

        foreach ($classes as $classe) {
            $data[] = [
                'id' => $classe->getId(),
                'nom' => $classe->getNom(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/options-by-classe/{id}', name: 'app_option_by_classe', methods: ['GET'])]
    public function optionsByClasse(
        Classe $classe,
        OptionRepository $optionRepository
    ): JsonResponse {
        $options = $optionRepository->findBy(
            ['classe' => $classe],
            ['nom' => 'ASC']
        );

        $data = [];

        foreach ($options as $option) {
            $data[] = [
                'id' => $option->getId(),
                'nom' => $option->getNom(),
            ];
        }

        return $this->json($data);
    }
}
