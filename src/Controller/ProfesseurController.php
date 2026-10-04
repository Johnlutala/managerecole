<?php

namespace App\Controller;

use App\Entity\Professeur;
use App\Entity\User;
use App\Entity\AnneeScolaire;
use App\Entity\Section;
use App\Form\ProfesseurType;
use App\Repository\AnneeScolaireRepository;
use App\Repository\EcoleRepository;
use App\Repository\ProfesseurRepository;
use App\Repository\SectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[Route('/professeur', name: 'app_professeur_')]
final class ProfesseurController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ProfesseurRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $professeur = new Professeur();
        $form = $this->createForm(ProfesseurType::class, $professeur);
        $form->handleRequest($request);

       foreach ($form->getErrors(true) as $error) {
    dd(
        'Champ : ' . ($error->getOrigin()?->getName() ?? 'formulaire'),
        'Erreur : ' . $error->getMessage()
    );
}


        if ($form->isSubmitted() && $form->isValid()) {

            if ($this->getUser() instanceof User) {
                $professeur->setCreatedBy($this->getUser());
            }
            $entityManager->persist($professeur);
            $entityManager->flush();
            $this->addFlash('success', 'Professeur créé(e) avec succès.');
            return $this->redirectToRoute('app_professeur_index');
        }
        return $this->render('professeur/index.html.twig', ['professeur' => $professeur, 'professeurs' => $repository->findAll(), 'form' => $form->createView()]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $professeur = new Professeur();
        $form = $this->createForm(ProfesseurType::class, $professeur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $professeur->setCreatedBy($this->getUser());
            }
            $entityManager->persist($professeur);
            $entityManager->flush();
            $this->addFlash('success', 'Professeur créé(e) avec succès.');
            return $this->redirectToRoute('app_professeur_index');
        }
        return $this->render('professeur/new.html.twig', ['professeur' => $professeur, 'form' => $form]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Professeur $professeur): Response
    {
        return $this->render('professeur/show.html.twig', ['professeur' => $professeur]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Professeur $professeur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProfesseurType::class, $professeur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Professeur mis(e) à jour avec succès.');
            return $this->redirectToRoute('app_professeur_index');
        }
        return $this->render('professeur/edit.html.twig', ['professeur' => $professeur, 'form' => $form]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Professeur $professeur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $professeur->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($professeur);
            $entityManager->flush();
            $this->addFlash('success', 'Professeur supprimé(e) avec succès.');
        }
        return $this->redirectToRoute('app_professeur_index');
    }

    #[Route('/dependances/{id}', name: 'dependances', methods: ['GET'])]
    public function dependances(
        int $id,
        EcoleRepository $ecoleRepository,
        AnneeScolaireRepository $anneeScolaireRepository,
        SectionRepository $sectionRepository
    ): JsonResponse {
        $ecole = $ecoleRepository->find($id);

        if (!$ecole) {
            return $this->json([
                'success' => false,
                'message' => 'École introuvable.'
            ], 404);
        }

        $annees = $anneeScolaireRepository->findBy([
            'ecole' => $ecole
        ]);

        $sections = $sectionRepository->findBy([
            'ecole' => $ecole
        ]);

        return $this->json([
            'success' => true,

            'annees' => array_map(function (AnneeScolaire $annee) {
                return [
                    'id' => $annee->getId(),
                    'label' => $annee->getLibelle(),
                ];
            }, $annees),

            'sections' => array_map(function (Section $section) {
                return [
                    'id' => $section->getId(),
                    'label' => $section->getNom(),
                ];
            }, $sections),
        ]);
    }
}
