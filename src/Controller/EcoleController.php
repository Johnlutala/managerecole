<?php
namespace App\Controller;
use App\Entity\Ecole;
use App\Entity\User;
use App\Form\EcoleType;
use App\Repository\EcoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ecole', name: 'app_ecole_')]
final class EcoleController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, EcoleRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $ecole = new Ecole();
        $form = $this->createForm(EcoleType::class, $ecole);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) { $ecole->setCreatedBy($this->getUser()); }
            $entityManager->persist($ecole);
            $entityManager->flush();
            $this->addFlash('success', 'École créé(e) avec succès.');
            return $this->redirectToRoute('app_ecole_index');
        }
        return $this->render('ecole/index.html.twig', ['ecole' => $ecole, 'ecoles' => $repository->findAll(), 'form' => $form->createView()]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ecole = new Ecole(); $form = $this->createForm(EcoleType::class, $ecole); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { if ($this->getUser() instanceof User) { $ecole->setCreatedBy($this->getUser()); } $entityManager->persist($ecole); $entityManager->flush(); $this->addFlash('success', 'École créé(e) avec succès.'); return $this->redirectToRoute('app_ecole_index'); }
        return $this->render('ecole/new.html.twig', ['ecole' => $ecole, 'form' => $form]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Ecole $ecole): Response { return $this->render('ecole/show.html.twig', ['ecole' => $ecole]); }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ecole $ecole, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EcoleType::class, $ecole); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { $entityManager->flush(); $this->addFlash('success', 'École mis(e) à jour avec succès.'); return $this->redirectToRoute('app_ecole_index'); }
        return $this->render('ecole/edit.html.twig', ['ecole' => $ecole, 'form' => $form]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Ecole $ecole, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ecole->getId(), $request->getPayload()->getString('_token'))) { $entityManager->remove($ecole); $entityManager->flush(); $this->addFlash('success', 'École supprimé(e) avec succès.'); }
        return $this->redirectToRoute('app_ecole_index');
    }
}