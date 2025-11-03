<?php

namespace App\Repository;

use App\Entity\Participant;
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
                // ->setMaxResults(10)
                ->getQuery()
                ->getResult()
            ;
        }
        
        return $this->createQueryBuilder('p')
            ->leftJoin('p.demographic', 'd')
            ->andWhere('d.firstname LIKE :keyword OR d.lastname LIKE :keyword')
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
