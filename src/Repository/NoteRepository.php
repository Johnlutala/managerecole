<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\AnneeScolaire;
use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Option;
use App\Entity\Section;
use App\Entity\Evaluation;
use App\Entity\Professeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * @param array<int, \App\Entity\Evaluation> $evaluations
     * @param array<int, \App\Entity\Eleve> $eleves
     * @return Note[]
     */
    public function findForEvaluationsAndEleves(array $evaluations, array $eleves): array
    {
        if ($evaluations === [] || $eleves === []) {
            return [];
        }

        return $this->createQueryBuilder('note')
            ->andWhere('note.evaluation IN (:evaluations)')
            ->andWhere('note.eleve IN (:eleves)')
            ->setParameter('evaluations', $evaluations)
            ->setParameter('eleves', $eleves)
            ->getQuery()
            ->getResult();
    }


    /**
     * @return array<int, array{evaluation: \App\Entity\Evaluation, total: string, soumises: string, validees: string}>
     */

    public function findSubmittedEvaluationSummaries(
        ?Ecole $ecole = null,
        ?Section $section = null,
        ?Classe $classe = null,
        ?Option $option = null,
        ?string $status = null
    ): array
    {
        $queryBuilder = $this->getEntityManager()->getRepository(Evaluation::class)
            ->createQueryBuilder('evaluation')
            ->select('evaluation.id AS evaluationId')
            ->addSelect('COUNT(note.id) AS total')
            ->addSelect("SUM(CASE WHEN note.statut = 'soumise' THEN 1 ELSE 0 END) AS soumises")
            ->addSelect("SUM(CASE WHEN note.statut = 'validee' THEN 1 ELSE 0 END) AS validees")
            ->addSelect('MAX(note.dateSoumission) AS dateSoumission')
            ->leftJoin(Note::class, 'note', 'WITH', 'note.evaluation = evaluation')
            ->innerJoin('evaluation.cours', 'cours')
            ->innerJoin('cours.classe', 'classe')
            ->groupBy('evaluation.id')
            ->orderBy('evaluation.id', 'DESC');

        if ($ecole !== null) {
            $queryBuilder->andWhere('classe.ecole = :ecole')->setParameter('ecole', $ecole);
        }
        if ($section !== null) {
            $queryBuilder->andWhere('classe.section = :section')->setParameter('section', $section);
        }
        if ($classe !== null) {
            $queryBuilder->andWhere('cours.classe = :classe')->setParameter('classe', $classe);
        }
        if ($option !== null) {
            $queryBuilder->andWhere('cours.option = :option')->setParameter('option', $option);
        }
        if ($status === 'soumise') {
            $queryBuilder->having("SUM(CASE WHEN note.statut = 'soumise' THEN 1 ELSE 0 END) > 0");
        } elseif ($status === 'validee') {
            $queryBuilder->having("SUM(CASE WHEN note.statut = 'validee' THEN 1 ELSE 0 END) > 0");
        } elseif ($status === 'non_soumise') {
            $queryBuilder->having("SUM(CASE WHEN note.statut IN ('soumise', 'validee') THEN 1 ELSE 0 END) = 0");
        }

        $rows = $queryBuilder->getQuery()->getResult();
        $evaluationIds = array_map(static fn (array $row): int => (int) $row['evaluationId'], $rows);
        $evaluationEntities = $evaluationIds === []
            ? []
            : $this->getEntityManager()->getRepository(Evaluation::class)->findBy(['id' => $evaluationIds]);
        $evaluationsById = [];
        foreach ($evaluationEntities as $evaluation) {
            $evaluationsById[$evaluation->getId()] = $evaluation;
        }

        return array_map(static function (array $row) use ($evaluationsById): array {
            return [
                'evaluation' => $evaluationsById[(int) $row['evaluationId']],
                'total' => $row['total'],
                'soumises' => $row['soumises'],
                'validees' => $row['validees'],
                'dateSoumission' => $row['dateSoumission'],
            ];
        }, $rows);
    }

    /** @return array<int, array{evaluation: Evaluation, validees: int}> */
    public function findValidatedEvaluationSummaries(
        AnneeScolaire $schoolYear,
        ?Ecole $school = null,
        ?Section $section = null,
        ?Classe $class = null,
        ?Option $option = null
    ): array {
        $queryBuilder = $this->getEntityManager()->getRepository(Evaluation::class)
            ->createQueryBuilder('evaluation')
            ->select('evaluation.id AS evaluationId')
            ->addSelect("SUM(CASE WHEN note.statut = 'validee' THEN 1 ELSE 0 END) AS validees")
            ->innerJoin('evaluation.configurationBulletin', 'configuration')
            ->innerJoin('configuration.classe', 'classe')
            ->innerJoin('evaluation.cours', 'cours')
            ->leftJoin(Note::class, 'note', 'WITH', 'note.evaluation = evaluation')
            ->andWhere('configuration.anneeScolaire = :schoolYear')
            ->andWhere('cours.classe = classe')
            ->andWhere('(configuration.options IS NULL OR configuration.options = cours.option)')
            ->setParameter('schoolYear', $schoolYear)
            ->groupBy('evaluation.id')
            ->having("SUM(CASE WHEN note.statut = 'validee' THEN 1 ELSE 0 END) > 0")
            ->orderBy('evaluation.id', 'ASC');

        if ($school !== null) {
            $queryBuilder->andWhere('configuration.ecole = :school')->setParameter('school', $school);
        }
        if ($section !== null) {
            $queryBuilder->andWhere('classe.section = :section')->setParameter('section', $section);
        }
        if ($class !== null) {
            $queryBuilder->andWhere('cours.classe = :class')->setParameter('class', $class);
        }
        if ($option !== null) {
            $queryBuilder->andWhere('(cours.option = :option OR cours.option IS NULL)')
                ->andWhere('(configuration.options IS NULL OR configuration.options = :option)')
                ->setParameter('option', $option);
        }

        $rows = $queryBuilder->getQuery()->getResult();
        $evaluationIds = array_map(static fn (array $row): int => (int) $row['evaluationId'], $rows);
        $evaluations = $evaluationIds === []
            ? []
            : $this->getEntityManager()->getRepository(Evaluation::class)->findBy(['id' => $evaluationIds]);
        $evaluationsById = [];
        foreach ($evaluations as $evaluation) {
            $evaluationsById[$evaluation->getId()] = $evaluation;
        }

        return array_map(static fn (array $row): array => [
            'evaluation' => $evaluationsById[(int) $row['evaluationId']],
            'validees' => (int) $row['validees'],
        ], $rows);
    }

    /**
     * @return int[]
     */
    public function findCoursesWithSubmittedNotes(): array
    {
        $rows = $this->createQueryBuilder('note')
            ->select('DISTINCT cours.id AS coursId')
            ->innerJoin('note.evaluation', 'evaluation')
            ->innerJoin('evaluation.cours', 'cours')
            ->andWhere('note.statut IN (:statuts)')
            ->setParameter('statuts', ['soumise', 'validee'])
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['coursId'], $rows);
    }

    /**
     * @return array<string, int>
     */
    public function countByProfessorAndStatus(Professeur $professeur): array
    {
        $counts = ['brouillon' => 0, 'soumise' => 0, 'validee' => 0];
        $rows = $this->createQueryBuilder('note')
            ->select('note.statut AS statut, COUNT(note.id) AS total')
            ->innerJoin('note.evaluation', 'evaluation')
            ->innerJoin('evaluation.cours', 'cours')
            ->andWhere('cours.professeur = :professeur')
            ->setParameter('professeur', $professeur)
            ->groupBy('note.statut')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            if (array_key_exists($row['statut'], $counts)) {
                $counts[$row['statut']] = (int) $row['total'];
            }
        }

        return $counts;
    }



    //    /**
    //     * @return Note[] Returns an array of Note objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('n.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Note
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
