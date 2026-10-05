<?php

namespace App\Repository;

use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Classe>
 */
class ClasseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classe::class);
    }

    /**
     * @return Classe[]
     */
    public function findOrdered(): array
    {
        return $this->createQueryBuilder('classe')
            ->addSelect("
                CASE
                    WHEN classe.nom LIKE '7e%' THEN 1
                    WHEN classe.nom LIKE '8e%' THEN 2
                    WHEN classe.nom LIKE '1e%' THEN 3
                    WHEN classe.nom LIKE '2e%' THEN 4
                    WHEN classe.nom LIKE '3e%' THEN 5
                    WHEN classe.nom LIKE '4e%' THEN 6
                    ELSE 99
                END AS HIDDEN ordre_classe
            ")
            ->orderBy('ordre_classe', 'ASC')
            ->addOrderBy('classe.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Classe[] Returns an array of Classe objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Classe
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
