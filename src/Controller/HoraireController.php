<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Cours;
use App\Entity\CreneauHoraire;
use App\Entity\Ecole;
use App\Entity\Horaire;
use App\Entity\Jour;
use App\Entity\Option;
use App\Entity\Section;
use App\Form\HoraireType;
use App\Repository\ClasseRepository;
use App\Repository\CoursRepository;
use App\Repository\CreneauHoraireRepository;
use App\Repository\EcoleRepository;
use App\Repository\HeureRepository;
use App\Repository\HoraireRepository;
use App\Repository\OptionRepository;
use App\Repository\SectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/horaire')]
final class HoraireController extends AbstractController
{
    #[Route(name: 'app_horaire_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        HoraireRepository $horaireRepository,
        CreneauHoraireRepository $creneauHoraireRepository,
        EcoleRepository $ecoleRepository,
        SectionRepository $sectionRepository,
        ClasseRepository $classeRepository,
        CoursRepository $coursRepository,
        OptionRepository $optionRepository,
        HeureRepository $heureRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $ecoles = $ecoleRepository->findBy([], ['id' => 'ASC']);
        $sections = $sectionRepository->findBy([], ['nom' => 'ASC']);
        $classes = $classeRepository->findOrdered();

        $options = array_values(array_filter(
            $optionRepository->findBy([], ['nom' => 'ASC']),
            static fn(Option $option): bool => $option->isDeleted() !== true
        ));

        $tousLesCours = $coursRepository->findBy([], ['nom' => 'ASC']);
        $heures = $heureRepository->findBy([], ['ordre' => 'ASC']);
        $jours = $entityManager->getRepository(Jour::class)->findBy([], ['ordre' => 'ASC']);

        if ($request->isMethod('POST')) {
            $ecoleId = $request->request->get('horaire_ecole');
            $sectionId = $request->request->get('horaire_section');
            $classeId = $request->request->get('horaire_classe');
            $optionId = $request->request->get('horaire_option');
        } else {
            $ecoleId = $request->query->get('ecole');
            $sectionId = $request->query->get('section');
            $classeId = $request->query->get('classe');
            $optionId = $request->query->get('option');
        }

        $selectedEcole = $ecoleId
            ? $ecoleRepository->find($ecoleId)
            : null;

        if ($selectedEcole === null && $ecoles !== []) {
            $selectedEcole = $ecoles[0];
        }
        $sectionsEcole = array_values(array_filter(
            $sections,
            static fn(Section $section): bool =>
            $section->getEcole()?->getId() === $selectedEcole?->getId()
        ));

        $selectedSection = $sectionId
            ? $sectionRepository->find($sectionId)
            : null;

        if (
            $selectedSection === null ||
            $selectedSection->getEcole()?->getId() !== $selectedEcole?->getId()
        ) {
            $selectedSection = $sectionsEcole[0] ?? null;
        }
        $classesSection = array_values(array_filter(
            $classes,
            static fn(Classe $classe): bool =>
            $classe->getSection()?->getId() === $selectedSection?->getId()
                && $classe->getEcole()?->getId() === $selectedEcole?->getId()
        ));

        $selectedClasse = $classeId
            ? $classeRepository->find($classeId)
            : null;

        if (
            $selectedClasse !== null
            && (
                $selectedClasse->getSection()?->getId() !== $selectedSection?->getId()
                || $selectedClasse->getEcole()?->getId() !== $selectedEcole?->getId()
            )
        ) {
            $selectedClasse = null;
        }
        if ($selectedClasse === null) {
            $selectedClasse = $classesSection[0] ?? null;
        }

        $optionsClasse = array_values(array_filter(
            $options,
            static fn(Option $option): bool =>
            $option->getClasse()?->getId() === $selectedClasse?->getId()
        ));
        $selectedOption = $optionId ? $optionRepository->find($optionId) : null;
        if (
            $selectedOption !== null &&
            !in_array($selectedOption, $optionsClasse, true)
        ) {
            $selectedOption = null;
        }

        $supportsOptionFilter = $selectedClasse !== null
            && preg_match('/^[1-4]e\b.*humanit[ée]/iu', $selectedClasse->getNom() ?? '') === 1;

        if (!$supportsOptionFilter) {
            $selectedOption = null;
        }

        if ($request->isMethod('POST')) {
            $redirectFilters = $this->buildFilterParameters(
                $selectedEcole,
                $selectedSection,
                $selectedClasse,
                $selectedOption
            );

            if (
                $selectedEcole === null ||
                $selectedSection === null ||
                $selectedClasse === null ||
                $selectedEcole->getId() !== $selectedClasse->getEcole()?->getId() ||
                $selectedSection->getId() !== $selectedClasse->getSection()?->getId() ||
                ($selectedOption !== null && $selectedOption->getClasse()?->getId() !== $selectedClasse->getId())
            ) {
                $this->addFlash('danger', 'Veuillez vérifier l’école, la section, la classe et l’option sélectionnées.');

                return $this->redirectToRoute('app_horaire_index', $redirectFilters);
            }

            $submittedSchedule = $request->request->all('planning');
            $coursesByCell = [];

            foreach ($submittedSchedule as $heureId => $dayValues) {
                $heure = $heureRepository->find($heureId);
                if ($heure === null || !in_array($heure, $heures, true)) {
                    $this->addFlash('danger', 'Un créneau horaire envoyé est invalide.');

                    return $this->redirectToRoute('app_horaire_index', $redirectFilters);
                }

                foreach ($dayValues as $jourId => $coursId) {
                    $jour = null;
                    foreach ($jours as $candidateJour) {
                        if ((string) $candidateJour->getId() === (string) $jourId) {
                            $jour = $candidateJour;
                            break;
                        }
                    }
                    if ($jour === null) {
                        $this->addFlash('danger', 'Un jour envoyé est invalide.');

                        return $this->redirectToRoute('app_horaire_index', $redirectFilters);
                    }

                    if ($coursId === '' || $coursId === null) {
                        $coursesByCell[$heure->getId()][$jour->getId()] = null;
                        continue;
                    }

                    $cours = $coursRepository->find($coursId);
                    if (
                        !$cours instanceof Cours ||
                        $cours->getClasse()?->getId() !== $selectedClasse->getId() ||
                        $cours->getOption()?->getId() !== $selectedOption?->getId()
                    ) {
                        $this->addFlash('danger', 'Un cours sélectionné ne correspond pas à la classe et à l’option choisies.');

                        return $this->redirectToRoute('app_horaire_index', $redirectFilters);
                    }

                    $coursesByCell[$heure->getId()][$jour->getId()] = $cours;
                }
            }

            $horaire = $horaireRepository->findForSelection(
                $selectedClasse,
                $selectedEcole,
                $selectedOption
            );

            if ($horaire === null) {
                $horaire = (new Horaire())
                    ->setEcole($selectedEcole)
                    ->setClasse($selectedClasse)
                    ->setOption($selectedOption);
                $entityManager->persist($horaire);
                $entityManager->flush();
            }

            $cellulesExistantes = [];
            foreach ($creneauHoraireRepository->findForHoraire($horaire) as $cellule) {
                $cellulesExistantes[$cellule->getHeure()->getId()][$cellule->getJour()->getId()] = $cellule;
            }

            foreach ($heures as $heure) {
                foreach ($jours as $jour) {
                    $cellule = $cellulesExistantes[$heure->getId()][$jour->getId()] ?? null;
                    if ($cellule === null) {
                        $cellule = (new CreneauHoraire())
                            ->setHoraire($horaire)
                            ->setHeure($heure)
                            ->setJour($jour);
                        $entityManager->persist($cellule);
                    }

                    $cours = $coursesByCell[$heure->getId()][$jour->getId()] ?? null;
                    $cellule
                        ->setCours($cours)
                        ->setProfesseur($cours?->getProfesseur())
                        ->setStatut($cours === null ? null : 'PREVU');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'L’horaire de la classe a été enregistré.');

            return $this->redirectToRoute('app_horaire_index', $redirectFilters);
        }

        $horaire = null;
        $planning = [];
        if ($selectedEcole !== null && $selectedClasse !== null) {
            $horaire = $horaireRepository->findForSelection(
                $selectedClasse,
                $selectedEcole,
                $selectedOption
            );

            if ($horaire !== null) {
                foreach ($creneauHoraireRepository->findForHoraire($horaire) as $cellule) {
                    $planning[$cellule->getHeure()->getId()][$cellule->getJour()->getId()] = $cellule;
                }
            }
        }

        return $this->render('horaire/index.html.twig', [
            'ecoles' => $ecoles,
            'sections' => $sections,
            'classes' => $classes,
            'options' => $options,
            'tousLesCours' => $tousLesCours,
            'heures' => $heures,
            'jours' => $jours,
            'planning' => $planning,
            'horaire' => $horaire,
            'selectedEcole' => $selectedEcole,
            'selectedSection' => $selectedSection,
            'selectedClasse' => $selectedClasse,
            'selectedOption' => $selectedOption,
            'optionsClasse' => $optionsClasse,
            'supportsOptionFilter' => $supportsOptionFilter,
        ]);
    }

    #[Route('/new', name: 'app_horaire_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->redirectToRoute('app_horaire_index');
    }

    #[Route('/{id}', name: 'app_horaire_show', methods: ['GET'])]
    public function show(Horaire $horaire): Response
    {
        return $this->redirectToRoute('app_horaire_index', $this->buildFilterParameters(
            $horaire->getEcole(),
            $horaire->getClasse()?->getSection(),
            $horaire->getClasse(),
            $horaire->getOption()
        ));
    }

    #[Route('/{id}/edit', name: 'app_horaire_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Horaire $horaire,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(HoraireType::class, $horaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'L’horaire a été modifié.');

            return $this->redirectToRoute('app_horaire_show', ['id' => $horaire->getId()]);
        }

        return $this->render('horaire/edit.html.twig', [
            'horaire' => $horaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_horaire_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Horaire $horaire,
        EntityManagerInterface $entityManager
    ): Response {
        $filters = $this->buildFilterParameters(
            $horaire->getEcole(),
            $horaire->getClasse()?->getSection(),
            $horaire->getClasse(),
            $horaire->getOption()
        );

        if ($this->isCsrfTokenValid(
            'delete' . $horaire->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $entityManager->remove($horaire);
            $entityManager->flush();
            $this->addFlash('success', 'L’horaire a été supprimé.');
        }

        return $this->redirectToRoute('app_horaire_index', $filters);
    }

    /**
     * @return array<string, int|string>
     */
    private function buildFilterParameters(
        ?Ecole $ecole,
        ?Section $section,
        ?Classe $classe,
        ?Option $option
    ): array {
        $parameters = [];
        if ($ecole !== null) {
            $parameters['ecole'] = $ecole->getId();
        }
        if ($section !== null) {
            $parameters['section'] = $section->getId();
        }
        if ($classe !== null) {
            $parameters['classe'] = $classe->getId();
        }
        if ($option !== null) {
            $parameters['option'] = $option->getId();
        }

        return $parameters;
    }
}
