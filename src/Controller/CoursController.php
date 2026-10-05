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
        $ecoles = $ecoleRepository->findBy([], ['id' => 'ASC']);
        $selectedEcole = null;
        $requestedEcoleId = $request->query->get('ecole');

        if ($requestedEcoleId !== null && $requestedEcoleId !== '') {
            $selectedEcole = $ecoleRepository->find($requestedEcoleId);
        }

        if ($selectedEcole === null) {
            $selectedEcole = $ecoles[0] ?? null;
        }

        $selectedClasse = null;
        $requestedClasseId = $request->query->get('classe');
        if ($selectedEcole && $requestedClasseId) {
            $candidateClasse = $entityManager->getRepository(Classe::class)->find($requestedClasseId);
            if ($candidateClasse?->getEcole() === $selectedEcole) {
                $selectedClasse = $candidateClasse;
            }
        }

        $selectedProfesseur = null;
        $requestedProfesseurId = $request->query->get('professeur');
        if ($selectedEcole && $requestedProfesseurId) {
            $candidateProfesseur = $entityManager->getRepository(Professeur::class)->find($requestedProfesseurId);
            if ($candidateProfesseur?->getEcole() === $selectedEcole) {
                $selectedProfesseur = $candidateProfesseur;
            }
        }

        $cour = new Cours();
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ecole = $form->get('ecole')->getData();
            $section = $form->get('section')->getData();
            $classe = $cour->getClasse();
            $option = $cour->getOption();

            if (
                !$ecole
                || !$section
                || !$classe
                || $section->getEcole() !== $ecole
                || $classe->getSection() !== $section
                || $classe->getEcole() !== $ecole
                || ($option && $option->getClasse() !== $classe)
            ) {
                $this->addFlash('danger', 'La section, la classe ou l’option ne correspond pas à l’école sélectionnée.');

                return $this->redirectToRoute('app_cours_index', [
                    'ecole' => $ecole?->getId() ?? $selectedEcole?->getId(),
                    'classe' => $selectedClasse?->getId(),
                    'professeur' => $selectedProfesseur?->getId(),
                    'add' => 1,
                ]);
            }

            if ($repository->findOneBy([
                'nom' => $cour->getNom(),
                'classe' => $classe,
                'option' => $option,
            ])) {
                $this->addFlash('warning', 'Ce cours est déjà enregistré pour cette classe et cette option.');

                return $this->redirectToRoute('app_cours_index', [
                    'ecole' => $ecole->getId(),
                    'classe' => $selectedClasse?->getId(),
                    'professeur' => $selectedProfesseur?->getId(),
                    'add' => 1,
                ]);
            }

            if ($this->getUser() instanceof User) {
                $cour->setCreatedBy($this->getUser());
            }

            $entityManager->persist($cour);
            $entityManager->flush();
            $this->addFlash('success', 'Cours créé(e) avec succès.');

            return $this->redirectToRoute('app_cours_index', [
                'ecole' => $ecole->getId(),
                'classe' => $selectedClasse?->getId(),
                'professeur' => $selectedProfesseur?->getId(),
                'add' => 1,
            ]);
        }

        $classes = $selectedEcole
            ? $entityManager->getRepository(Classe::class)->findBy(['ecole' => $selectedEcole], ['nom' => 'ASC'])
            : [];
        $professeurs = $selectedEcole
            ? $entityManager->getRepository(Professeur::class)->findBy(['ecole' => $selectedEcole], ['nom' => 'ASC'])
            : [];
        $cours = $selectedEcole
            ? $repository->findForEcole($selectedEcole, $selectedClasse, $selectedProfesseur)
            : [];

        $coursParClasse = [];
        $coursParClasseEtProfesseur = [];
        foreach ($cours as $coursItem) {
            $classeId = $coursItem->getClasse()->getId();
            if (!isset($coursParClasse[$classeId])) {
                $coursParClasse[$classeId] = [
                    'classe' => $coursItem->getClasse(),
                    'cours' => [],
                    'professeurs' => [],
                ];
            }
            $coursParClasse[$classeId]['cours'][] = $coursItem;

            $professeur = $coursItem->getProfesseur();
            $professeurId = $professeur?->getId() ?? 0;
            $coursParClasse[$classeId]['professeurs'][$professeurId] = $professeur
                ? trim($professeur->getNom() . ' ' . $professeur->getPrenom())
                : 'Non attribué';

            $professeurId = $coursItem->getProfesseur()?->getId() ?? 0;
            $groupeId = $classeId . '-' . $professeurId;

            if (!isset($coursParClasseEtProfesseur[$groupeId])) {
                $coursParClasseEtProfesseur[$groupeId] = [
                    'classe' => $coursItem->getClasse(),
                    'professeur' => $coursItem->getProfesseur(),
                    'cours' => [],
                ];
            }

            $coursParClasseEtProfesseur[$groupeId]['cours'][] = $coursItem;
        }

        $coursParClasse = array_map(
            static function (array $groupe): array {
                $groupe['professeurs'] = array_values($groupe['professeurs']);

                return $groupe;
            },
            array_values($coursParClasse)
        );

        return $this->render('cours/index.html.twig', [
            'cour' => $cour,
            'cours' => $cours,
            'coursParClasse' => $coursParClasse,
            'coursParClasseEtProfesseur' => array_values($coursParClasseEtProfesseur),
            'allCours' => $repository->findAll(),
            'ecoles' => $ecoles,
            'selectedEcole' => $selectedEcole,
            'classes' => $classes,
            'professeurs' => $professeurs,
            'selectedClasse' => $selectedClasse,
            'selectedProfesseur' => $selectedProfesseur,
            'openAddModal' => $request->query->getBoolean('add') || $form->isSubmitted(),
            'openGestionModal' => $request->query->getBoolean('gestion'),
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
        $ecoleId = $request->request->get('ecole');
        $sectionId = $request->request->get('section');
        $professeurId = $request->request->get('professeur');
        $classeId = $request->request->get('classe');
        $optionId = $request->request->get('option');

        if (!$courseId || !$ecoleId || !$sectionId || !$classeId || !$professeurId) {
            $this->addFlash('danger', 'Veuillez sélectionner l’école, la section, la classe, le cours et le professeur.');
            return $this->redirectToRoute('app_cours_index');
        }

        $cour = $entityManager->getRepository(Cours::class)->find($courseId);
        $professeur = $entityManager->getRepository(Professeur::class)->find($professeurId);
        $ecole = $entityManager->getRepository(Ecole::class)->find($ecoleId);
        $section = $entityManager->getRepository(Section::class)->find($sectionId);
        $classe = $entityManager->getRepository(Classe::class)->find($classeId);
        $option = $optionId ? $entityManager->getRepository(Option::class)->find($optionId) : null;

        if (!$cour || !$professeur || !$ecole || !$section || !$classe || ($optionId && !$option)) {
            $this->addFlash('danger', 'Une sélection est introuvable. Veuillez recommencer.');
            return $this->redirectToRoute('app_cours_index');
        }

        if (
            $section->getEcole() !== $ecole
            || $classe->getSection() !== $section
            || $classe->getEcole() !== $ecole
            || $professeur->getEcole() !== $ecole
            || $cour->getClasse() !== $classe
            || $cour->getOption() !== $option
            || ($option && $option->getClasse() !== $classe)
        ) {
            $this->addFlash('danger', 'Le cours, l’option, la classe et le professeur doivent appartenir à l’école sélectionnée.');

            return $this->redirectToRoute('app_cours_index');
        }

        $cour->setProfesseur($professeur);
        $entityManager->flush();
        $this->addFlash('success', 'Cours attribué avec succès.');

        return $this->redirectToRoute('app_cours_index', [
            'ecole' => $ecole->getId(),
            'gestion' => 1,
        ]);
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

    #[Route('/classe/{id}', name: 'classe_show', methods: ['GET'])]
    public function showClasse(Classe $classe, CoursRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $cours = $repository->findBy(['classe' => $classe], ['nom' => 'ASC']);
        $professeurs = $classe->getEcole()
            ? $entityManager->getRepository(Professeur::class)->findBy(
                ['ecole' => $classe->getEcole()],
                ['nom' => 'ASC']
            )
            : [];

        return $this->render('cours/show.html.twig', [
            'classe' => $classe,
            'cours' => $cours,
            'professeurs' => $professeurs,
        ]);
    }

    #[Route('/classe/{id}/attributions', name: 'classe_assignments', methods: ['POST'])]
    public function updateClasseAssignments(
        Request $request,
        Classe $classe,
        CoursRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('manage_class_courses_' . $classe->getId(), $request->request->getString('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_cours_classe_show', ['id' => $classe->getId()]);
        }

        if ($request->request->has('assignments')) {
            $submittedAssignments = $request->request->all('assignments');
            $classCourses = $repository->findBy(['classe' => $classe]);

            if (count($submittedAssignments) !== count($classCourses)) {
                $this->addFlash('danger', 'La liste des cours à modifier est invalide.');

                return $this->redirectToRoute('app_cours_classe_show', ['id' => $classe->getId()]);
            }

            $validatedAssignments = [];
            foreach ($classCourses as $cour) {
                $courseId = (string) $cour->getId();
                if (!array_key_exists($courseId, $submittedAssignments)) {
                    $this->addFlash('danger', 'La liste des cours à modifier est incomplète.');

                    return $this->redirectToRoute('app_cours_classe_show', ['id' => $classe->getId()]);
                }

                $submittedProfessorId = $submittedAssignments[$courseId];
                if (!is_string($submittedProfessorId) && !is_int($submittedProfessorId)) {
                    $this->addFlash('danger', 'Une sélection de professeur est invalide.');

                    return $this->redirectToRoute('app_cours_classe_show', ['id' => $classe->getId()]);
                }

                $professeurId = (string) $submittedProfessorId;
                $professeur = $entityManager->getRepository(Professeur::class)->find($professeurId);
                if (
                    !$professeur
                    || !$classe->getEcole()
                    || $professeur->getEcole()?->getId() !== $classe->getEcole()->getId()
                ) {
                    $this->addFlash('danger', 'Chaque cours doit avoir un professeur de la même école.');

                    return $this->redirectToRoute('app_cours_classe_show', ['id' => $classe->getId()]);
                }

                $validatedAssignments[] = [$cour, $professeur];
            }

            foreach ($validatedAssignments as [$cour, $professeur]) {
                $cour->setProfesseur($professeur);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Les professeurs des cours de la classe ont été mis à jour.');

            return $this->redirectToRoute('app_cours_classe_show', ['id' => $classe->getId()]);
        }

        $courseId = $request->request->getString('cours');
        $cour = $repository->find($courseId);
        if (!$cour || $cour->getClasse() !== $classe) {
            $this->addFlash('danger', 'Le cours sélectionné ne fait pas partie de cette classe.');

            return $this->redirectToRoute('app_cours_classe_show', ['id' => $classe->getId()]);
        }

        if ($request->request->get('action') === 'remove') {
            $cour->setProfesseur(null);
            $entityManager->flush();
            $this->addFlash('success', 'Le professeur a été retiré de ce cours.');

            return $this->redirectToRoute('app_cours_classe_show', ['id' => $classe->getId()]);
        }

        $professeurId = $request->request->getString('professeur');
        $professeur = $professeurId
            ? $entityManager->getRepository(Professeur::class)->find($professeurId)
            : null;

        if (
            !$professeur
            || !$classe->getEcole()
            || $professeur->getEcole()?->getId() !== $classe->getEcole()->getId()
        ) {
            $this->addFlash('danger', 'Veuillez sélectionner un professeur de la même école.');

            return $this->redirectToRoute('app_cours_classe_show', ['id' => $classe->getId()]);
        }

        $cour->setProfesseur($professeur);
        $entityManager->flush();
        $this->addFlash('success', 'L’attribution du cours a été mise à jour.');

        return $this->redirectToRoute('app_cours_classe_show', ['id' => $classe->getId()]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Cours $cour): Response
    {
        return $this->render('cours/show_cour.html.twig', ['cour' => $cour]);
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
