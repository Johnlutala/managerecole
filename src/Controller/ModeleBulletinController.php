<?php

namespace App\Controller;

use App\Entity\ModeleBulletin;
use App\Form\ModeleBulletinType;
use App\Repository\ClasseRepository;
use App\Repository\ModeleBulletinRepository;
use App\Repository\OptionRepository;
use App\Repository\SectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/modele/bulletin')]
#[IsGranted('ROLE_ADMIN')]
final class ModeleBulletinController extends AbstractController
{
    /**
     * Liste des modèles de bulletins et formulaire de création en modale.
     */
    #[Route(name: 'app_modele_bulletin_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ModeleBulletinRepository $modeleBulletinRepository,
        SectionRepository $sectionRepository,
        ClasseRepository $classeRepository,
        OptionRepository $optionRepository,
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

        $sections = array_values(array_filter(
            $sectionRepository->findBy(['deleted' => false], ['nom' => 'ASC']),
            static fn ($section): bool => !$section->isDeleted()
        ));
        $sectionId = $request->query->getInt('section') ?: null;
        $selectedSection = null;
        foreach ($sections as $section) {
            if ($section->getId() === $sectionId) {
                $selectedSection = $section;
                break;
            }
        }

        $classes = $selectedSection === null
            ? []
            : array_values(array_filter(
                $classeRepository->findBy(['section' => $selectedSection], ['nom' => 'ASC']),
                static fn ($classe): bool => !$classe->isDeleted()
            ));
        $classId = $request->query->getInt('classe') ?: null;
        $selectedClass = null;
        foreach ($classes as $classe) {
            if ($classe->getId() === $classId) {
                $selectedClass = $classe;
                break;
            }
        }

        $options = $selectedClass === null
            ? []
            : array_values(array_filter(
                $optionRepository->findBy(['classe' => $selectedClass], ['nom' => 'ASC']),
                static fn ($option): bool => !$option->isDeleted()
            ));
        $optionId = $request->query->getInt('option') ?: null;
        $selectedOption = null;
        foreach ($options as $option) {
            if ($option->getId() === $optionId) {
                $selectedOption = $option;
                break;
            }
        }
        if ($selectedClass !== null && $options === []) {
            $selectedOption = 0;
        }

        return $this->render('modele_bulletin/index.html.twig', [
            'modele_bulletins' => $modeleBulletinRepository->findBy([], [
                'id' => 'DESC',
            ]),
            'form' => $form,
            'sections' => $sections,
            'classes' => $classes,
            'options' => $options,
            'section_selectionnee' => $selectedSection,
            'classe_selectionnee' => $selectedClass,
            'option_selectionnee' => $selectedOption,
            'contexte_selectionne' => $selectedSection !== null
                && $selectedClass !== null
                && $selectedOption !== null,
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
