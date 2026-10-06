<?php

namespace App\Controller;

use App\Entity\Section;
use App\Entity\User;
use App\Form\SectionType;
use App\Repository\EcoleRepository;
use App\Repository\SectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/section', name: 'app_section_',)]
final class SectionController extends AbstractController
{
    #[Route("", name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SectionRepository $sectionRepository,
        EcoleRepository $ecoleRepository,
        EntityManagerInterface $entityManager
    ): Response {


        $ecoles = $ecoleRepository->findBy([], ['id' => 'ASC']);
        $selectedEcole = null;
        $requestedEcoleId = $request->query->get('ecole');

        if ($requestedEcoleId !== null && $requestedEcoleId !== '') {
            $selectedEcole = $ecoleRepository->find($requestedEcoleId);
        }
        if ($selectedEcole === null) {
            $selectedEcole = $ecoles[0] ?? null;
        }
        $section = new Section();

        $form = $this->createForm(SectionType::class, $section);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($this->getUser() instanceof User) {
                $section->setCreatedBy($this->getUser());
            }

            $entityManager->persist($section);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Section créée avec succès.'
            );

            return $this->redirectToRoute('app_section_index', [
                'ecole' => $selectedEcole?->getId(),
            ]);
        }

        // SECTIONS
        $sections = $selectedEcole
            ? $sectionRepository->findBy(
                [
                    'ecole' => $selectedEcole,
                    'deleted' => false,
                ],
                [
                    'id' => 'ASC',
                ]
            )
            : [];

        return $this->render('section/index.html.twig', [
            'sections' => $sections,
            'section' => $section,
            'form' => $form->createView(),

            // Écoles pour le filtre
            'ecoles' => $ecoles,

            // École actuellement sélectionnée
            'selectedEcole' => $selectedEcole,
        ]);
    }


    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $section = new Section();
        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $section->setCreatedBy($this->getUser());
            }
            $entityManager->persist($section);
            $entityManager->flush();

            $this->addFlash('success', 'Section créée avec succès.');

            return $this->redirectToRoute('app_section_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('section/new.html.twig', [
            'section' => $section,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Section $section): Response
    {
        return $this->render('section/show.html.twig', [
            'section' => $section,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Section $section, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Section mise à jour avec succès.');

            return $this->redirectToRoute('app_section_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('section/edit.html.twig', [
            'section' => $section,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Section $section, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $section->getId(), $request->getPayload()->getString('_token'))) {
            $section->setDeleted(true);
            $section->setEnabled(false);
            $entityManager->flush();
            $this->addFlash('success', 'Section supprimée avec succès.');
        }

        return $this->redirectToRoute('app_section_index', [], Response::HTTP_SEE_OTHER);
    }
}
