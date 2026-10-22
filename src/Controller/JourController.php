<?php

namespace App\Controller;

use App\Entity\Jour;
use App\Form\JourType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/jour')]
final class JourController extends AbstractController
{
    #[Route(name: 'app_jour_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Créer un nouveau jour pour le formulaire de la modale
        $jour = new Jour();

        $form = $this->createForm(JourType::class, $jour);
        $form->handleRequest($request);

        // Enregistrer le jour si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($jour);
            $entityManager->flush();

            $this->addFlash('success', 'Le jour a été ajouté avec succès.');

            return $this->redirectToRoute(
                'app_jour_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        // Récupérer les jours dans l'ordre croissant
        $jours = $entityManager
            ->getRepository(Jour::class)
            ->findBy([], ['ordre' => 'ASC']);

        return $this->render('jour/index.html.twig', [
            'jours' => $jours,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_jour_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $jour = new Jour();

        $form = $this->createForm(JourType::class, $jour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($jour);
            $entityManager->flush();

            $this->addFlash('success', 'Le jour a été ajouté avec succès.');

            return $this->redirectToRoute(
                'app_jour_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('jour/new.html.twig', [
            'jour' => $jour,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_jour_show', methods: ['GET'])]
    public function show(Jour $jour): Response
    {
        return $this->render('jour/show.html.twig', [
            'jour' => $jour,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_jour_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Jour $jour,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(JourType::class, $jour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le jour a été modifié avec succès.');

            return $this->redirectToRoute(
                'app_jour_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('jour/edit.html.twig', [
            'jour' => $jour,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_jour_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Jour $jour,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete' . $jour->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $entityManager->remove($jour);
            $entityManager->flush();

            $this->addFlash('success', 'Le jour a été supprimé avec succès.');
        }

        return $this->redirectToRoute(
            'app_jour_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
