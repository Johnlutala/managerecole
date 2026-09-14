<?php
namespace App\Controller;
use App\Entity\Eleve;
use App\Entity\User;
use App\Form\EleveType;
use App\Repository\EleveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/eleve', name: 'app_eleve_')]
final class EleveController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, EleveRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $eleve = new Eleve();
        $form = $this->createForm(EleveType::class, $eleve);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) { $eleve->setCreatedBy($this->getUser()); }
            $entityManager->persist($eleve);
            $entityManager->flush();
            $this->addFlash('success', 'Élève créé(e) avec succès.');
            return $this->redirectToRoute('app_eleve_index');
        }
        return $this->render('eleve/index.html.twig', ['eleve' => $eleve, 'eleves' => $repository->findAll(), 'form' => $form->createView()]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $eleve = new Eleve(); $form = $this->createForm(EleveType::class, $eleve); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { if ($this->getUser() instanceof User) { $eleve->setCreatedBy($this->getUser()); } $entityManager->persist($eleve); $entityManager->flush(); $this->addFlash('success', 'Élève créé(e) avec succès.'); return $this->redirectToRoute('app_eleve_index'); }
        return $this->render('eleve/new.html.twig', ['eleve' => $eleve, 'form' => $form]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Eleve $eleve): Response { return $this->render('eleve/show.html.twig', ['eleve' => $eleve]); }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Eleve $eleve, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EleveType::class, $eleve); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { $entityManager->flush(); $this->addFlash('success', 'Élève mis(e) à jour avec succès.'); return $this->redirectToRoute('app_eleve_index'); }
        return $this->render('eleve/edit.html.twig', ['eleve' => $eleve, 'form' => $form]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Eleve $eleve, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$eleve->getId(), $request->getPayload()->getString('_token'))) { $entityManager->remove($eleve); $entityManager->flush(); $this->addFlash('success', 'Élève supprimé(e) avec succès.'); }
        return $this->redirectToRoute('app_eleve_index');
    }
}