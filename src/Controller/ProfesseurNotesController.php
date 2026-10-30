<?php

namespace App\Controller;

use App\Entity\ConfigurationBulletin;
use App\Entity\Cours;
use App\Entity\Evaluation;
use App\Entity\Note;
use App\Entity\Professeur;
use App\Entity\User;
use App\Repository\ConfigurationBulletinRepository;
use App\Repository\CoursRepository;
use App\Repository\EleveRepository;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/professeur/notes', name: 'app_professeur_notes_')]
#[IsGranted('ROLE_PROFESSEUR')]
final class ProfesseurNotesController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, CoursRepository $coursRepository): Response
    {
        $professeur = $this->professeurConnecte();
        $courses = $this->coursAttribues($coursRepository, $professeur);
        $schools = [];

        foreach ($courses as $course) {
            $school = $course->getClasse()?->getEcole();
            if ($school !== null) {
                $schools[$school->getId()] = $school;
            }
        }

        uasort($schools, static fn ($a, $b) => strcasecmp($a->getNom(), $b->getNom()));
        $querySchools = $request->query->all()['ecoles'] ?? [];
        $querySchools = is_array($querySchools) ? $querySchools : [];
        $selectedSchoolIds = array_values(array_unique(array_map(
            'intval',
            array_filter(
                $querySchools,
                static fn ($id): bool => is_scalar($id) && ctype_digit((string) $id)
                    && (int) $id > 0
                    && isset($schools[(int) $id])
            )
        )));
        $selectedSchoolSet = array_fill_keys($selectedSchoolIds, true);
        $showAllSchools = $selectedSchoolIds === [];
        $classes = [];

        foreach ($courses as $course) {
            $class = $course->getClasse();
            $school = $class?->getEcole();
            if (
                $class === null
                || $school === null
                || (!$showAllSchools && !isset($selectedSchoolSet[$school->getId()]))
            ) {
                continue;
            }

            $classId = $class->getId();
            $classes[$classId] ??= ['classe' => $class, 'cours' => []];
            $classes[$classId]['cours'][] = $course;
        }

        uasort($classes, static fn ($a, $b) => strcasecmp($a['classe']->getNom(), $b['classe']->getNom()));
        foreach ($classes as &$classData) {
            usort($classData['cours'], static fn (Cours $a, Cours $b) => strcasecmp($a->getNom(), $b->getNom()));
        }
        unset($classData);

        return $this->render('professeur/notes.html.twig', [
            'classes' => $classes,
            'ecoles' => array_values($schools),
            'ecoles_selectionnees' => $selectedSchoolIds,
        ]);
    }

    #[Route('/cours/{id}', name: 'cours', methods: ['GET', 'POST'])]
    public function cours(
        int $id,
        Request $request,
        CoursRepository $coursRepository,
        ConfigurationBulletinRepository $configurationRepository,
        EleveRepository $eleveRepository,
        NoteRepository $noteRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $professeur = $this->professeurConnecte();
        $cours = $coursRepository->findOneBy(['id' => $id, 'professeur' => $professeur]);
        if (!$cours instanceof Cours || $cours->getClasse() === null || $cours->getClasse()->getEcole() === null) {
            throw $this->createNotFoundException();
        }

        $classe = $cours->getClasse();
        $criteresEleves = ['classe' => $classe, 'statut' => true, 'deleted' => false];
        // Un cours d'option ne concerne que les élèves inscrits dans cette option.
        if ($cours->getOption() !== null) {
            $criteresEleves['option'] = $cours->getOption();
        }
        $eleves = $eleveRepository->findBy(
            $criteresEleves,
            ['nom' => 'ASC', 'prenom' => 'ASC']
        );
        $configurations = array_values(array_filter(
            $configurationRepository->findBy(['classe' => $classe]),
            fn (ConfigurationBulletin $configuration): bool => $this->configurationPourCours($configuration, $cours, $professeur)
        ));
        $periods = [];
        $evaluationIds = [];
        foreach ($configurations as $configuration) {
            $evaluations = array_values(array_filter(
                $configuration->getEvaluations()->toArray(),
                static fn (Evaluation $evaluation): bool => $evaluation->getCours()?->getId() === $cours->getId()
            ));
            usort($evaluations, static fn (Evaluation $a, Evaluation $b) => strcasecmp($a->getNom(), $b->getNom()));
            foreach ($evaluations as $evaluation) {
                $evaluationIds[$evaluation->getId()] = $evaluation;
            }
            $periods[$configuration->getId()] = [
                'configuration' => $configuration,
                'evaluations' => $evaluations,
                'ouverte' => $configuration->getStatut() === 'autorise',
                'notes' => [],
                'total_max' => array_sum(array_map(
                    static fn (Evaluation $evaluation): int => $evaluation->getBareme() ?? 0,
                    $evaluations
                )),
                'totaux' => [],
            ];
        }

        $studentIds = [];
        foreach ($eleves as $eleve) {
            $studentIds[$eleve->getId()] = true;
        }
        if ($evaluationIds !== []) {
            foreach ($noteRepository->findForEvaluationsAndEleves(array_values($evaluationIds), $eleves) as $note) {
                $studentId = $note->getEleve()?->getId();
                $configurationId = $note->getEvaluation()?->getConfigurationBulletin()?->getId();
                if (
                    $studentId === null
                    || !isset($studentIds[$studentId])
                    || !isset($periods[$configurationId])
                ) {
                    continue;
                }

                $evaluationId = $note->getEvaluation()->getId();
                $periods[$configurationId]['notes'][$evaluationId][$studentId] = $note;
                $periods[$configurationId]['totaux'][$studentId] =
                    ($periods[$configurationId]['totaux'][$studentId] ?? 0) + (int) $note->getCote();
            }
        }

        if ($request->isMethod('POST')) {
            $configurationId = $request->query->getInt('periode');
            $configuration = $configurationRepository->find($configurationId);
            if (
                !$configuration instanceof ConfigurationBulletin
                || !isset($periods[$configurationId])
                || !$periods[$configurationId]['ouverte']
                || !$this->isCsrfTokenValid(
                    'notes_' . $cours->getId() . '_' . $configurationId,
                    (string) $request->request->get('_token')
                )
            ) {
                throw $this->createAccessDeniedException('Cette période ne permet pas la saisie des notes.');
            }

            $action = (string) $request->request->get('action');
            if (!in_array($action, ['enregistrer', 'soumettre'], true)) {
                throw $this->createAccessDeniedException();
            }
            $changes = [];
            $draftsToSubmit = 0;
            $submittedGrades = $request->request->all()['notes'] ?? [];
            if (!is_array($submittedGrades)) {
                throw $this->createAccessDeniedException();
            }
            $period = $periods[$configurationId];
            $periodEvaluationIds = array_fill_keys(
                array_map(static fn (Evaluation $evaluation): int => $evaluation->getId(), $period['evaluations']),
                true
            );

            foreach ($submittedGrades as $evaluationId => $studentGrades) {
                if (!isset($periodEvaluationIds[(int) $evaluationId]) || !is_array($studentGrades)) {
                    throw $this->createAccessDeniedException();
                }
                foreach ($studentGrades as $studentId => $grade) {
                    if (!isset($studentIds[(int) $studentId]) || !is_scalar($grade)) {
                        throw $this->createAccessDeniedException();
                    }
                }
            }

            foreach ($period['evaluations'] as $evaluation) {
                foreach ($eleves as $eleve) {
                    $evaluationId = $evaluation->getId();
                    $studentId = $eleve->getId();
                    if (
                        !isset($submittedGrades[$evaluationId])
                        || !is_array($submittedGrades[$evaluationId])
                        || !array_key_exists($studentId, $submittedGrades[$evaluationId])
                    ) {
                        continue;
                    }
                    $rawGrade = trim((string) ($submittedGrades[$evaluationId][$studentId] ?? ''));
                    $note = $period['notes'][$evaluationId][$studentId] ?? null;

                    if ($note instanceof Note && in_array($note->getStatut(), ['soumise', 'validee'], true)) {
                        if ($rawGrade !== (string) $note->getCote()) {
                            throw $this->createAccessDeniedException('Une note soumise ou validée ne peut plus être modifiée.');
                        }
                        continue;
                    }

                    if ($rawGrade === '') {
                        if ($note instanceof Note) {
                            $changes[] = ['action' => 'supprimer', 'note' => $note];
                        }
                        continue;
                    }

                    if (
                        !preg_match('/^\d+$/D', $rawGrade)
                        || (int) $rawGrade > ($evaluation->getBareme() ?? 0)
                    ) {
                        $this->addFlash('danger', sprintf(
                            'La note de %s pour « %s » doit être un nombre entier entre 0 et %d.',
                            $eleve->getNom(),
                            $evaluation->getNom(),
                            $evaluation->getBareme() ?? 0
                        ));

                        return $this->redirectToRoute('app_professeur_notes_cours', ['id' => $cours->getId()]);
                    }

                    if ($action === 'soumettre') {
                        ++$draftsToSubmit;
                    }
                    if (!$note instanceof Note || $rawGrade !== (string) $note->getCote() || $action === 'soumettre') {
                        $changes[] = [
                            'action' => 'enregistrer',
                            'note' => $note ?? (new Note())->setEleve($eleve)->setEvaluation($evaluation),
                            'cote' => $rawGrade,
                        ];
                    }
                }
            }

            if ($action === 'soumettre' && $draftsToSubmit === 0) {
                $this->addFlash('warning', 'Aucune nouvelle note enregistrée à soumettre.');

                return $this->redirectToRoute('app_professeur_notes_cours', ['id' => $cours->getId()]);
            }

            foreach ($changes as $change) {
                if ($change['action'] === 'supprimer') {
                    $entityManager->remove($change['note']);
                    continue;
                }

                $note = $change['note'];
                $note->setCote($change['cote']);
                if ($action === 'soumettre') {
                    $note->setStatut('soumise');
                }
                if ($note->getId() === null) {
                    $entityManager->persist($note);
                }
            }
            $entityManager->flush();
            $this->addFlash(
                'success',
                $action === 'soumettre' ? 'Les notes ont été soumises à l’administration.' : 'Les notes ont été enregistrées.'
            );

            return $this->redirectToRoute('app_professeur_notes_cours', ['id' => $cours->getId()]);
        }

        return $this->render('professeur/notes_cours.html.twig', [
            'cours' => $cours,
            'eleves' => $eleves,
            'periodes' => array_values($periods),
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

    /**
     * @return Cours[]
     */
    private function coursAttribues(CoursRepository $coursRepository, Professeur $professeur): array
    {
        return array_values(array_filter(
            $coursRepository->findBy(['professeur' => $professeur]),
            static fn (Cours $cours): bool => !$cours->isDeleted()
                && $cours->getClasse() !== null
                && !$cours->getClasse()->isDeleted()
                && $cours->getClasse()->getEcole() !== null
        ));
    }

    private function configurationPourCours(
        ConfigurationBulletin $configuration,
        Cours $cours,
        Professeur $professeur
    ): bool {
        $classe = $cours->getClasse();
        $ecole = $classe?->getEcole();
        if (
            $classe === null
            || $ecole === null
            || $configuration->getEcole()?->getId() !== $ecole->getId()
            || $configuration->getClasse()?->getId() !== $classe->getId()
            || (
                $configuration->getOptions() !== null
                && $configuration->getOptions()?->getId() !== $cours->getOption()?->getId()
            )
        ) {
            return false;
        }

        $anneeProfesseur = $professeur->getAnneeScolaire();
        $anneeConfiguration = $configuration->getAnneeScolaire();

        return $anneeProfesseur === null
            || $anneeProfesseur->getId() === $anneeConfiguration?->getId();
    }
}
