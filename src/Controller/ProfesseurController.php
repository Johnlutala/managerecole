<?php
namespace App\Controller;
use App\Entity\Professeur;
use App\Entity\User;
use App\Form\ProfesseurType;
use App\Repository\ProfesseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/professeur', name: 'app_professeur_')]
final class ProfesseurController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, ProfesseurRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $professeur = new Professeur();
        $form = $this->createForm(ProfesseurType::class, $professeur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) { $professeur->setCreatedBy($this->getUser()); }
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
        $professeur = new Professeur(); $form = $this->createForm(ProfesseurType::class, $professeur); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { if ($this->getUser() instanceof User) { $professeur->setCreatedBy($this->getUser()); } $entityManager->persist($professeur); $entityManager->flush(); $this->addFlash('success', 'Professeur créé(e) avec succès.'); return $this->redirectToRoute('app_professeur_index'); }
        return $this->render('professeur/new.html.twig', ['professeur' => $professeur, 'form' => $form]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Professeur $professeur): Response { return $this->render('professeur/show.html.twig', ['professeur' => $professeur]); }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Professeur $professeur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProfesseurType::class, $professeur); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { $entityManager->flush(); $this->addFlash('success', 'Professeur mis(e) à jour avec succès.'); return $this->redirectToRoute('app_professeur_index'); }
        return $this->render('professeur/edit.html.twig', ['professeur' => $professeur, 'form' => $form]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Professeur $professeur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$professeur->getId(), $request->getPayload()->getString('_token'))) { $entityManager->remove($professeur); $entityManager->flush(); $this->addFlash('success', 'Professeur supprimé(e) avec succès.'); }
        return $this->redirectToRoute('app_professeur_index');
    }
}