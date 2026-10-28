<?php

namespace App\Repository;

use App\Entity\Parents;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Parents>
 */
class ParentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parents::class);
    }

    /**
     * @return Parents[]
     */
    public function searchByIdentity(string $term): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('LOWER(p.nom) LIKE LOWER(:term)')
            ->orWhere('LOWER(p.postnom) LIKE LOWER(:term)')
            ->orWhere('LOWER(p.prenom) LIKE LOWER(:term)')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('p.nom', 'ASC')
            ->addOrderBy('p.postnom', 'ASC')
            ->addOrderBy('p.prenom', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Parents[] Returns an array of Parents objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Parents
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
