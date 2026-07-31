<?php

namespace App\Controller;

use App\Entity\Workshop;
use App\Form\WorkshopType;
use App\Repository\WorkshopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/workshop', name: 'app_workshop_')]
final class WorkshopController extends AbstractController
{

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        WorkshopRepository $workshopRepository,
        EntityManagerInterface $entityManager
    ): Response {

        $status = $request->query->get('status');

        if ($status === '0') {
            $workshops = $workshopRepository->findByStatus(false);
        } elseif ($status === '1') {
            $workshops = $workshopRepository->findByStatus(true);
        } else {
            $workshops = $workshopRepository->findAllNotDeleted();
        }

        $workshop = new Workshop();

        $form = $this->createForm(WorkshopType::class, $workshop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($workshop);
            $entityManager->flush();

            $this->addFlash('success', 'Atelier créé avec succès.');

            return $this->redirectToRoute('app_workshop_index');
        }


        return $this->render('workshop/index.html.twig', [
            'workshops' => $workshops,
            'form' => $form->createView(),
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $workshop = new Workshop();
        $form = $this->createForm(WorkshopType::class, $workshop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($workshop);
            $entityManager->flush();

            return $this->redirectToRoute('app_workshop_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('workshop/new.html.twig', [
            'workshop' => $workshop,
            'form' => $form,
        ]);
    }

     #[Route('/delete-selected', name: 'delete_selected', methods: ['POST'])]
    public function deleteSelected(
        Request $request,
        WorkshopRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {

        if (
            !$this->isCsrfTokenValid(
                'delete_selected',
                $request->request->get('_token')
            )
        ) {

            throw $this->createAccessDeniedException();
        }

        $ids = $request->request->get('ids');

        if ($ids) {

            foreach (explode(',', $ids) as $id) {

                $workshop = $repository->find($id);

                if ($workshop) {

                    $workshop->setDeleted(true);
                    $entityManager->persist($workshop);
                }
            }

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Les ateliers ont été supprimés.'
            );
        }

        return $this->redirectToRoute('app_workshop_index');
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Workshop $workshop): Response
    {
        return $this->render('workshop/show.html.twig', [
            'workshop' => $workshop,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Workshop $workshop, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(WorkshopType::class, $workshop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_workshop_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('workshop/edit.html.twig', [
            'workshop' => $workshop,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Workshop $workshop, EntityManagerInterface $entityManager): Response
    {

        if ($this->isCsrfTokenValid('delete' . $workshop->getId(), $request->getPayload()->getString('_token'))) {
        
             $workshop->setDeleted(true);
             $entityManager->persist($workshop);
            $entityManager->flush();
            
            $this->addFlash(
                'success',
                'Les ateliers ont été supprimés.'
            );
        }

        return $this->redirectToRoute('app_workshop_index', [], Response::HTTP_SEE_OTHER);
    }

   
}
