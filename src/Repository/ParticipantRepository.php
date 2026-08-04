<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Entity\Workshop;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participant>
 */
class ParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /**
     * @return Participant[] Returns an array of Participant objects
     */
    public function findActive(?string $keyword): array
    {

        if (!$keyword) {
            # code...
            return $this->createQueryBuilder('p')
                ->andWhere('p.enabled = :enabled')
                ->setParameter('enabled', true)
                ->andWhere('p.deleted = :deleted')
                ->setParameter('deleted', false)
                ->orderBy('p.id', 'ASC')
                ->getQuery()
                ->getResult()
            ;
        }

        return $this->createQueryBuilder('p')
            ->andWhere('p.firstname LIKE :keyword OR p.lastname LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->andWhere('p.enabled = :enabled')
            ->setParameter('enabled', true)
            ->andWhere('p.deleted = :deleted')
            ->setParameter('deleted', false)
            ->orderBy('p.id', 'ASC')
            // ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

public function findActiveByUser(User $user): array
{
    return $this->createQueryBuilder('p')
        ->andWhere('p.enabled = :enabled')
        ->setParameter('enabled', true)
        ->andWhere('p.deleted = :deleted')
        ->setParameter('deleted', false)
        ->andWhere('p.createdBy = :user')
        ->setParameter('user', $user)
        ->orderBy('p.id', 'ASC')
        ->getQuery()
        ->getResult();
}


    /**
     * @return Participant[] Returns an array of Participant objects that are
     * active and not associated with any participation in the specified workshop
     */
    public function findActiveWithoutParticipation(?string $keyword, ?Workshop $workshop): array
    {
        // On récupère proprement l'EntityManager
        $entityManager = $this->getEntityManager();

        // Requête principale sur l'entité courante (Participant)
        $qb = $this->createQueryBuilder('p');

        // 1. Sous-requête pour récupérer les IDs des participants liés à l'atelier
        $subQb = $entityManager->createQueryBuilder()
            ->select('IDENTITY(part.participant)')
            ->from(\App\Entity\Participation::class, 'part')
            ->where('part.workshop = :workshop');

        // 2. Application des filtres globaux
        $qb->andWhere('p.enabled = :enabled')
            ->setParameter('enabled', true)
            ->andWhere('p.deleted = :deleted')
            ->setParameter('deleted', false)
            ->andWhere($qb->expr()->notIn('p.id', $subQb->getDQL())) // Exclusion
            ->setParameter('workshop', $workshop)
            ->orderBy('p.id', 'ASC');

        // 3. Filtre de recherche par mot-clé (optionnel)
        if ($keyword) {
            $qb->andWhere('p.firstname LIKE :keyword OR p.lastname LIKE :keyword')
                ->setParameter('keyword', '%' . $keyword . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countNotDeleted(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.deleted = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

public function findAllNotDeletedByUser(User $user): array
{
    return $this->createQueryBuilder('p')
        ->andWhere('p.deleted = :deleted')
        ->setParameter('deleted', false)
        ->andWhere('p.createdBy = :user')
        ->setParameter('user', $user)
        ->orderBy('p.id', 'ASC')
        ->getQuery()
        ->getResult();
}


    //    public function findOneBySomeField($value): ?Participant
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
