<?php
namespace App\Controller;
use App\Entity\Cours;
use App\Entity\User;
use App\Form\CoursType;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cours', name: 'app_cours_')]
final class CoursController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, CoursRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $cour = new Cours();
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) { $cour->setCreatedBy($this->getUser()); }
            $entityManager->persist($cour);
            $entityManager->flush();
            $this->addFlash('success', 'Cours créé(e) avec succès.');
            return $this->redirectToRoute('app_cours_index');
        }
        return $this->render('cours/index.html.twig', ['cour' => $cour, 'cours' => $repository->findAll(), 'form' => $form->createView()]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cour = new Cours(); $form = $this->createForm(CoursType::class, $cour); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { if ($this->getUser() instanceof User) { $cour->setCreatedBy($this->getUser()); } $entityManager->persist($cour); $entityManager->flush(); $this->addFlash('success', 'Cours créé(e) avec succès.'); return $this->redirectToRoute('app_cours_index'); }
        return $this->render('cours/new.html.twig', ['cour' => $cour, 'form' => $form]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Cours $cour): Response { return $this->render('cours/show.html.twig', ['cour' => $cour]); }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CoursType::class, $cour); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { $entityManager->flush(); $this->addFlash('success', 'Cours mis(e) à jour avec succès.'); return $this->redirectToRoute('app_cours_index'); }
        return $this->render('cours/edit.html.twig', ['cour' => $cour, 'form' => $form]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cour->getId(), $request->getPayload()->getString('_token'))) { $entityManager->remove($cour); $entityManager->flush(); $this->addFlash('success', 'Cours supprimé(e) avec succès.'); }
        return $this->redirectToRoute('app_cours_index');
    }
}