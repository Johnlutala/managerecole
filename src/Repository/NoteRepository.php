<?php

namespace App\Repository;

use App\Entity\Note;
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

    public function findSubmittedEvaluationSummaries(): array
    {
        $rows = $this->createQueryBuilder('note')
            ->select('evaluation.id AS evaluationId')
            ->addSelect('COUNT(note.id) AS total')
            ->addSelect("SUM(CASE WHEN note.statut = 'soumise' THEN 1 ELSE 0 END) AS soumises")
            ->addSelect("SUM(CASE WHEN note.statut = 'validee' THEN 1 ELSE 0 END) AS validees")
            ->innerJoin('note.evaluation', 'evaluation')
            ->andWhere('note.statut IN (:statuts)')
            ->setParameter('statuts', ['soumise', 'validee'])
            ->groupBy('evaluation.id')
            ->orderBy('evaluation.id', 'DESC')
            ->getQuery()
            ->getResult();

        $evaluationRepository = $this->getEntityManager()
            ->getRepository(\App\Entity\Evaluation::class);

        return array_map(function (array $row) use ($evaluationRepository): array {
            return [
                'evaluation' => $evaluationRepository->find($row['evaluationId']),
                'total' => $row['total'],
                'soumises' => $row['soumises'],
                'validees' => $row['validees'],
            ];
        }, $rows);
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
