<?php

namespace App\Controller;

use App\Entity\ModeleBulletin;
use App\Form\ModeleBulletinType;
use App\Repository\ModeleBulletinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/modele/bulletin')]
final class ModeleBulletinController extends AbstractController
{
    /**
     * Liste des modèles de bulletins et formulaire de création en modale.
     */
    #[Route(name: 'app_modele_bulletin_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ModeleBulletinRepository $modeleBulletinRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $modeleBulletin = new ModeleBulletin();

        $form = $this->createForm(ModeleBulletinType::class, $modeleBulletin, [
            'action' => $this->generateUrl('app_modele_bulletin_index'),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Initialiser le statut si aucun statut n'a été fourni.
            if (!$modeleBulletin->getStatut()) {
                $modeleBulletin->setStatut('actif');
            }

            // La date de création est gérée automatiquement si nécessaire
            // par l'entité ModeleBulletin.
            $entityManager->persist($modeleBulletin);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le modèle de bulletin a été créé avec succès.'
            );

            return $this->redirectToRoute(
                'app_modele_bulletin_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('modele_bulletin/index.html.twig', [
            'modele_bulletins' => $modeleBulletinRepository->findBy([], [
                'id' => 'DESC',
            ]),
            'form' => $form,
        ]);
    }

    /**
     * Consultation d'un modèle.
     */
    #[Route('/{id}', name: 'app_modele_bulletin_show', methods: ['GET'])]
    public function show(ModeleBulletin $modeleBulletin): Response
    {
        return $this->render('modele_bulletin/show.html.twig', [
            'modele_bulletin' => $modeleBulletin,
        ]);
    }

    /**
     * Modification d'un modèle.
     */
    #[Route('/{id}/edit', name: 'app_modele_bulletin_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ModeleBulletin $modeleBulletin,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ModeleBulletinType::class, $modeleBulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le modèle de bulletin a été modifié avec succès.'
            );

            return $this->redirectToRoute(
                'app_modele_bulletin_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('modele_bulletin/edit.html.twig', [
            'modele_bulletin' => $modeleBulletin,
            'form' => $form,
        ]);
    }

    /**
     * Suppression d'un modèle.
     */
    #[Route('/{id}', name: 'app_modele_bulletin_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        ModeleBulletin $modeleBulletin,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete' . $modeleBulletin->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $entityManager->remove($modeleBulletin);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le modèle de bulletin a été supprimé avec succès.'
            );
        } else {
            $this->addFlash(
                'danger',
                'Le jeton de sécurité est invalide. La suppression a été annulée.'
            );
        }

        return $this->redirectToRoute(
            'app_modele_bulletin_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
