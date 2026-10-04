<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Cours;
use App\Entity\Ecole;
use App\Entity\Option;
use App\Entity\Professeur;
use App\Entity\Section;
use App\Entity\User;
use App\Form\CoursType;
use App\Repository\CoursRepository;
use App\Repository\EcoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cours', name: 'app_cours_')]
final class CoursController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, CoursRepository $repository, EcoleRepository $ecoleRepository, EntityManagerInterface $entityManager): Response
    {
        $cour = new Cours();
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $cour->setCreatedBy($this->getUser());
            }

            $entityManager->persist($cour);
            $entityManager->flush();
            $this->addFlash('success', 'Cours créé(e) avec succès.');

            return $this->redirectToRoute('app_cours_index');
        }

        return $this->render('cours/index.html.twig', [
            'cour' => $cour,
            'cours' => $repository->findAll(),
            'ecoles' => $ecoleRepository->findBy([], ['nom' => 'ASC']),
            'form' => $form->createView(),
        ]);
    }
    #[Route('/ajax/sections/{ecole}', name: 'ajax_sections', methods: ['GET'])]
    public function ajaxSections(
        Ecole $ecole,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $sections = $entityManager
            ->getRepository(Section::class)
            ->findBy(['ecole' => $ecole], ['nom' => 'ASC']);

        return $this->json(array_map(
            fn(Section $section) => [
                'id' => $section->getId(),
                'nom' => $section->getNom(),
            ],
            $sections
        ));
    }
    #[Route('/ajax/classes/{section}', name: 'ajax_classes', methods: ['GET'])]
    public function ajaxClasses(
        Section $section,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $classes = $entityManager
            ->getRepository(Classe::class)
            ->findBy(['section' => $section], ['nom' => 'ASC']);

        return $this->json(array_map(
            fn(Classe $classe) => [
                'id' => $classe->getId(),
                'nom' => $classe->getNom(),
            ],
            $classes
        ));
    }
    #[Route('/ajax/options/{classe}', name: 'ajax_options', methods: ['GET'])]
    public function ajaxOptions(
        Classe $classe,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $options = $entityManager
            ->getRepository(Option::class)
            ->findBy(['classe' => $classe], ['nom' => 'ASC']);

        return $this->json(array_map(
            fn(Option $option) => [
                'id' => $option->getId(),
                'nom' => $option->getNom(),
            ],
            $options
        ));
    }

    #[Route('/ajax/professeurs/{ecole}', name: 'ajax_professeurs', methods: ['GET'])]
    public function ajaxProfesseurs(
        Ecole $ecole,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $professeurs = $entityManager
            ->getRepository(Professeur::class)
            ->findBy(['ecole' => $ecole], ['nom' => 'ASC']);

        return $this->json(array_map(
            fn(Professeur $professeur) => [
                'id' => $professeur->getId(),
                'nom' => trim(
                    $professeur->getNom() . ' ' . $professeur->getPrenom()
                ),
            ],
            $professeurs
        ));
    }
    #[Route('/assigner', name: 'assign', methods: ['POST'])]
    public function assign(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('assign_course', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_cours_index');
        }

        $courseId = $request->request->get('cours');
        $professeurId = $request->request->get('professeur');
        $classeId = $request->request->get('classe');

        if (!$courseId || !$professeurId) {
            $this->addFlash('danger', 'Veuillez sélectionner un cours et un professeur.');
            return $this->redirectToRoute('app_cours_index');
        }

        $cour = $entityManager->getRepository(Cours::class)->find($courseId);
        $professeur = $entityManager->getRepository(Professeur::class)->find($professeurId);

        if (!$cour || !$professeur) {
            $this->addFlash('danger', 'Le cours ou le professeur sélectionné est introuvable.');
            return $this->redirectToRoute('app_cours_index');
        }

        if ($classeId) {
            $classe = $entityManager->getRepository(Classe::class)->find($classeId);
            if ($classe) {
                $cour->setClasse($classe);
            }
        }

        $cour->setProfesseur($professeur);
        $entityManager->flush();
        $this->addFlash('success', 'Cours attribué avec succès.');

        return $this->redirectToRoute('app_cours_index');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cour = new Cours();
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $cour->setCreatedBy($this->getUser());
            }

            $entityManager->persist($cour);
            $entityManager->flush();
            $this->addFlash('success', 'Cours créé(e) avec succès.');

            return $this->redirectToRoute('app_cours_index');
        }

        return $this->render('cours/new.html.twig', ['cour' => $cour, 'form' => $form]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Cours $cour): Response
    {
        return $this->render('cours/show.html.twig', ['cour' => $cour]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Cours mis(e) à jour avec succès.');

            return $this->redirectToRoute('app_cours_index');
        }

        return $this->render('cours/edit.html.twig', ['cour' => $cour, 'form' => $form]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $cour->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($cour);
            $entityManager->flush();
            $this->addFlash('success', 'Cours supprimé(e) avec succès.');
        }

        return $this->redirectToRoute('app_cours_index');
    }
}
