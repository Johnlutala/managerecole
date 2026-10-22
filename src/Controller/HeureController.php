<?php

namespace App\Controller;

use App\Entity\Heure;
use App\Form\HeureType;
use App\Repository\HeureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/heure')]
final class HeureController extends AbstractController
{
    #[Route(name: 'app_heure_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        HeureRepository $heureRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $heure = new Heure();

        $form = $this->createForm(
            HeureType::class,
            $heure
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($heure);
            $entityManager->flush();

            return $this->redirectToRoute(
                'app_heure_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('heure/index.html.twig', [
            'heures' => $heureRepository->findBy(
                [],
                ['ordre' => 'ASC']
            ),
            'form' => $form,
        ]);
    }

    #[Route('/new', name: 'app_heure_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $heure = new Heure();

        $form = $this->createForm(
            HeureType::class,
            $heure
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($heure);
            $entityManager->flush();

            return $this->redirectToRoute(
                'app_heure_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('heure/new.html.twig', [
            'heure' => $heure,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_heure_show', methods: ['GET'])]
    public function show(Heure $heure): Response
    {
        return $this->render('heure/show.html.twig', [
            'heure' => $heure,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_heure_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Heure $heure,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(
            HeureType::class,
            $heure
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute(
                'app_heure_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('heure/edit.html.twig', [
            'heure' => $heure,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_heure_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Heure $heure,
        EntityManagerInterface $entityManager
    ): Response {
        if (
            $this->isCsrfTokenValid(
                'delete' . $heure->getId(),
                $request->getPayload()->getString('_token')
            )
        ) {
            $entityManager->remove($heure);
            $entityManager->flush();
        }

        return $this->redirectToRoute(
            'app_heure_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
