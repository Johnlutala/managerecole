<?php
namespace App\Controller;
use App\Entity\Classe;
use App\Entity\User;
use App\Form\ClasseType;
use App\Repository\ClasseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/classe', name: 'app_classe_')]
final class ClasseController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, ClasseRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $classe = new Classe();
        $form = $this->createForm(ClasseType::class, $classe);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) { $classe->setCreatedBy($this->getUser()); }
            $entityManager->persist($classe);
            $entityManager->flush();
            $this->addFlash('success', 'Classe créé(e) avec succès.');
            return $this->redirectToRoute('app_classe_index');
        }
        return $this->render('classe/index.html.twig', ['classe' => $classe, 'classes' => $repository->findAll(), 'form' => $form->createView()]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $classe = new Classe(); $form = $this->createForm(ClasseType::class, $classe); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { if ($this->getUser() instanceof User) { $classe->setCreatedBy($this->getUser()); } $entityManager->persist($classe); $entityManager->flush(); $this->addFlash('success', 'Classe créé(e) avec succès.'); return $this->redirectToRoute('app_classe_index'); }
        return $this->render('classe/new.html.twig', ['classe' => $classe, 'form' => $form]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Classe $classe): Response { return $this->render('classe/show.html.twig', ['classe' => $classe]); }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Classe $classe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClasseType::class, $classe); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { $entityManager->flush(); $this->addFlash('success', 'Classe mis(e) à jour avec succès.'); return $this->redirectToRoute('app_classe_index'); }
        return $this->render('classe/edit.html.twig', ['classe' => $classe, 'form' => $form]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Classe $classe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$classe->getId(), $request->getPayload()->getString('_token'))) { $entityManager->remove($classe); $entityManager->flush(); $this->addFlash('success', 'Classe supprimé(e) avec succès.'); }
        return $this->redirectToRoute('app_classe_index');
    }
}