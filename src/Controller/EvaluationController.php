<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Form\EvaluationType;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/evaluation')]
#[IsGranted('ROLE_ADMIN')]
final class EvaluationController extends AbstractController
{
    #[Route(name: 'app_evaluation_index', methods: ['GET'])]
    public function index(EvaluationRepository $evaluationRepository): Response
    {
        return $this->render('evaluation/index.html.twig', [
            'evaluations' => $evaluationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_evaluation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evaluation = new Evaluation();
        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->evaluationEstCoherente($evaluation)) {
                $this->addFlash('danger', 'Vérifiez la période, le type, le cours, la classe et l’option, puis évitez de préparer deux fois le même type pour une période.');

                return $this->render('evaluation/new.html.twig', [
                    'evaluation' => $evaluation,
                    'form' => $form,
                ]);
            }
            $entityManager->persist($evaluation);
            $entityManager->flush();

            return $this->redirectToRoute('app_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evaluation/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evaluation_show', methods: ['GET'])]
    public function show(Evaluation $evaluation): Response
    {
        return $this->render('evaluation/show.html.twig', [
            'evaluation' => $evaluation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evaluation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evaluation $evaluation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->evaluationEstCoherente($evaluation)) {
                $this->addFlash('danger', 'Vérifiez la période, le type, le cours, la classe et l’option, puis évitez de préparer deux fois le même type pour une période.');

                return $this->render('evaluation/edit.html.twig', [
                    'evaluation' => $evaluation,
                    'form' => $form,
                ]);
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evaluation_delete', methods: ['POST'])]
    public function delete(Request $request, Evaluation $evaluation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evaluation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($evaluation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_evaluation_index', [], Response::HTTP_SEE_OTHER);
    }

    private function evaluationEstCoherente(Evaluation $evaluation): bool
    {
        $cours = $evaluation->getCours();
        $configuration = $evaluation->getConfigurationBulletin();

        return $cours !== null
            && $configuration !== null
            && $this->periodeAutoriseePourSemestre($evaluation)
            && $cours->getClasse()?->getId() === $configuration->getClasse()?->getId()
            && $cours->getClasse()?->getEcole()?->getId() === $configuration->getEcole()?->getId()
            && ($configuration->getOptions() === null || $cours->getOption()?->getId() === $configuration->getOptions()?->getId())
            && (
                $cours->getProfesseur()?->getAnneeScolaire() === null
                || $cours->getProfesseur()?->getAnneeScolaire()?->getId() === $configuration->getAnneeScolaire()?->getId()
            )
            && $this->cotationUniqueDansLeCours($evaluation);
    }

    private function cotationUniqueDansLeCours(Evaluation $evaluation): bool
    {
        $configuration = $evaluation->getConfigurationBulletin();
        $cours = $evaluation->getCours();
        if ($configuration === null || $cours === null) {
            return false;
        }

        foreach ($configuration->getEvaluations() as $existing) {
            if (
                $existing->getId() !== $evaluation->getId()
                && $existing->getCours()?->getId() === $cours->getId()
                && strcasecmp(trim((string) $existing->getNom()), trim((string) $evaluation->getNom())) === 0
                && strcasecmp(trim((string) $existing->getType()), trim((string) $evaluation->getType())) === 0
            ) {
                return false;
            }
        }

        return true;
    }

    private function periodeAutoriseePourSemestre(Evaluation $evaluation): bool
    {
        $nomPeriode = $evaluation->getConfigurationBulletin()?->getNom();
        if (
            $nomPeriode === null
            || preg_match('/^(Semestre|Trimestre)\s+(I{1,3}|[1-3])$/iu', trim($nomPeriode), $matches) !== 1
        ) {
            return false;
        }

        $kind = strtolower($matches[1]);
        $number = match (strtoupper($matches[2])) {
            'I', '1' => 1,
            'II', '2' => 2,
            'III', '3' => 3,
        };
        if (($kind === 'semestre' && $number > 2) || ($kind === 'trimestre' && $number > 3)) {
            return false;
        }

        $firstPeriod = $kind === 'semestre' ? (($number - 1) * 2 + 1) : (($number - 1) * 2 + 1);
        $periodes = [
            'Période ' . $firstPeriod,
            'Période ' . ($firstPeriod + 1),
            'Examen ' . $number,
        ];

        foreach ($periodes as $periode) {
            if (strcasecmp(trim((string) $evaluation->getNom()), $periode) === 0) {
                return true;
            }
        }

        return false;
    }
}
