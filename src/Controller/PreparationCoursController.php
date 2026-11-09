<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\PreparationCours;
use App\Entity\Professeur;
use App\Entity\User;
use App\Form\PreparationCoursType;
use App\Repository\CoursRepository;
use App\Repository\PreparationCoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/preparations-cours', name: 'app_preparation_cours_')]
final class PreparationCoursController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_PROFESSEUR')]
    public function index(CoursRepository $coursRepository, PreparationCoursRepository $preparationRepository): Response
    {
        $professeur = $this->professeurConnecte();
        $cours = array_values(array_filter(
            $coursRepository->findBy(['professeur' => $professeur]),
            static fn (Cours $item): bool => !$item->isDeleted()
                && $item->getClasse() !== null
                && $item->getClasse()->getEcole() !== null
        ));

        $preparationsParCours = [];
        foreach ($preparationRepository->findForProfesseur($professeur) as $preparation) {
            $preparationsParCours[$preparation->getCours()->getId()][] = $preparation;
        }

        return $this->render('preparations_cours/index.html.twig', [
            'cours' => $cours,
            'preparations_par_cours' => $preparationsParCours,
            'direction' => false,
        ]);
    }

    #[Route('/nouvelle/{cours}', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PROFESSEUR')]
    public function new(
        Cours $cours,
        Request $request,
        PreparationCoursRepository $preparationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $professeur = $this->professeurConnecte();
        $this->verifierCoursDuProfesseur($cours, $professeur);

        $preparation = (new PreparationCours())
            ->setProfesseur($professeur)
            ->setCours($cours);
        foreach ($cours->getHoraires() as $creneau) {
            if (
                $creneau->getProfesseur() !== null
                && $creneau->getProfesseur()->getId() !== $professeur->getId()
            ) {
                continue;
            }
            $jour = $creneau->getJour()?->getNom();
            $heure = $creneau->getHeure();
            $heureDebut = $heure?->getHeureDebut();
            $heureFin = $heure?->getHeureFin();
            if ($jour !== null && $heureDebut !== null && $heureFin !== null) {
                $preparation
                    ->setJour($jour)
                    ->setHeureDebut(clone $heureDebut)
                    ->setHeureFin(clone $heureFin);
                break;
            }
        }
        $form = $this->createForm(PreparationCoursType::class, $preparation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $preparation->setNumero($preparationRepository->prochainNumero($professeur, $cours));
            $preparation->setCreatedBy($this->getUser() instanceof User ? $this->getUser() : null);
            $entityManager->persist($preparation);
            $entityManager->flush();

            $this->addFlash('success', sprintf('La préparation n°%d a été enregistrée en brouillon.', $preparation->getNumero()));

            return $this->redirectToRoute('app_preparation_cours_show', ['id' => $preparation->getId()]);
        }

        return $this->render('preparations_cours/form.html.twig', [
            'form' => $form,
            'preparation' => $preparation,
            'cours' => $cours,
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PROFESSEUR')]
    public function edit(PreparationCours $preparation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->verifierProprietaire($preparation);
        if (!in_array($preparation->getStatut(), [PreparationCours::BROUILLON, PreparationCours::A_CORRIGER], true)) {
            throw $this->createAccessDeniedException('Cette préparation ne peut plus être modifiée.');
        }

        $form = $this->createForm(PreparationCoursType::class, $preparation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($preparation->getStatut() === PreparationCours::A_CORRIGER) {
                $preparation->setStatut(PreparationCours::BROUILLON)
                    ->setTraiteeLe(null)
                    ->setTraiteePar(null);
            }
            $entityManager->flush();
            $this->addFlash('success', 'La préparation a été enregistrée.');

            return $this->redirectToRoute('app_preparation_cours_show', ['id' => $preparation->getId()]);
        }

        return $this->render('preparations_cours/form.html.twig', [
            'form' => $form,
            'preparation' => $preparation,
            'cours' => $preparation->getCours(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(PreparationCours $preparation): Response
    {
        $this->verifierAcces($preparation);

        return $this->render('preparations_cours/show.html.twig', [
            'preparation' => $preparation,
            'direction' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/{id}/soumettre', name: 'submit', methods: ['POST'])]
    #[IsGranted('ROLE_PROFESSEUR')]
    public function submit(PreparationCours $preparation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->verifierProprietaire($preparation);
        if (!$this->isCsrfTokenValid('preparation-submit-'.$preparation->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }
        if (!in_array($preparation->getStatut(), [PreparationCours::BROUILLON, PreparationCours::A_CORRIGER], true)) {
            throw $this->createAccessDeniedException('Cette préparation a déjà été soumise.');
        }

        $preparation->setStatut(PreparationCours::SOUMISE)
            ->setSoumiseLe(new \DateTimeImmutable())
            ->setCommentaireDirection(null)
            ->setTraiteeLe(null)
            ->setTraiteePar(null);
        $entityManager->flush();
        $this->addFlash('success', 'La préparation a été transmise à la direction.');

        return $this->redirectToRoute('app_preparation_cours_show', ['id' => $preparation->getId()]);
    }

    #[Route('/direction', name: 'direction', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function direction(PreparationCoursRepository $repository): Response
    {
        return $this->render('preparations_cours/index.html.twig', [
            'cours' => [],
            'preparations_par_cours' => [],
            'preparations_soumises' => $repository->findSoumises(),
            'direction' => true,
        ]);
    }

    #[Route('/direction/{id}/{decision}', name: 'review', methods: ['POST'], requirements: ['decision' => 'valider|corriger'])]
    #[IsGranted('ROLE_ADMIN')]
    public function review(
        PreparationCours $preparation,
        string $decision,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('preparation-review-'.$preparation->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }
        if ($preparation->getStatut() !== PreparationCours::SOUMISE) {
            throw $this->createAccessDeniedException('Cette préparation a déjà été traitée.');
        }

        $commentaire = trim(strip_tags((string) $request->request->get('commentaire', '')));
        $preparation->setStatut($decision === 'valider' ? PreparationCours::VALIDEE : PreparationCours::A_CORRIGER)
            ->setCommentaireDirection($commentaire !== '' ? $commentaire : null)
            ->setTraiteeLe(new \DateTimeImmutable())
            ->setTraiteePar($this->getUser() instanceof User ? $this->getUser() : null);
        $entityManager->flush();
        $this->addFlash('success', $decision === 'valider' ? 'La préparation a été validée.' : 'La préparation a été retournée à l’enseignant.');

        return $this->redirectToRoute('app_preparation_cours_direction');
    }

    #[Route('/{id}/pdf', name: 'pdf', methods: ['GET'])]
    public function pdf(PreparationCours $preparation): Response
    {
        $this->verifierAcces($preparation);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderView('preparations_cours/pdf.html.twig', [
            'preparation' => $preparation,
        ]), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf(
                'attachment; filename="preparation-%s-%02d.pdf"',
                preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $preparation->getCours()?->getNom()),
                $preparation->getNumero()
            ),
        ]);
    }

    #[Route('/cours/{cours}/historique.pdf', name: 'history_pdf', methods: ['GET'])]
    public function historyPdf(
        Cours $cours,
        PreparationCoursRepository $repository
    ): Response {
        $professeur = null;
        if (!$this->isGranted('ROLE_ADMIN')) {
            if (!$this->isGranted('ROLE_PROFESSEUR')) {
                throw $this->createAccessDeniedException();
            }
            $professeur = $this->professeurConnecte();
            $this->verifierCoursDuProfesseur($cours, $professeur);
        }
        $preparations = $repository->findForCours($cours, $professeur);
        if ($preparations === []) {
            throw $this->createNotFoundException('Aucune préparation enregistrée pour ce cours.');
        }

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderView('preparations_cours/history_pdf.html.twig', [
            'preparations' => $preparations,
        ]), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf(
                'attachment; filename="historique-%s.pdf"',
                preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $cours->getNom())
            ),
        ]);
    }

    private function professeurConnecte(): Professeur
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getProfesseur() instanceof Professeur) {
            throw $this->createAccessDeniedException('Aucun profil professeur n’est associé à ce compte.');
        }

        return $user->getProfesseur();
    }

    private function verifierCoursDuProfesseur(Cours $cours, Professeur $professeur): void
    {
        if ($cours->getProfesseur()?->getId() !== $professeur->getId()
            || $cours->isDeleted()
            || $cours->getClasse()?->getEcole() === null
        ) {
            throw $this->createAccessDeniedException('Ce cours ne vous est pas attribué.');
        }
    }

    private function verifierProprietaire(PreparationCours $preparation): void
    {
        if ($preparation->getProfesseur()?->getId() !== $this->professeurConnecte()->getId()) {
            throw $this->createAccessDeniedException('Cette préparation ne vous appartient pas.');
        }
    }

    private function verifierAcces(PreparationCours $preparation): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        $this->verifierProprietaire($preparation);
    }
}
