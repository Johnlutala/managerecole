<?php

namespace App\Repository;

use App\Entity\Ecole;
use App\Entity\Classe;
use App\Entity\Option;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Option>
 */
class OptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Option::class);
    }

    /**
     * @return Option[]
     */
    public function findForClasse(?Classe $classe): array
    {
        if ($classe === null) {
            return [];
        }

        return $this->createQueryBuilder('o')
            ->andWhere('o.classe = :classe')
            ->andWhere('(o.deleted = false OR o.deleted IS NULL)')
            ->setParameter('classe', $classe)
            ->orderBy('o.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForEcole(?Ecole $ecole): array
{
    if (!$ecole) {
        return [];
    }

    return $this->createQueryBuilder('o')
        ->join('o.classe', 'c')
        ->andWhere('c.ecole = :ecole')
        ->andWhere('o.deleted = :deleted')
        ->setParameter('ecole', $ecole)
        ->setParameter('deleted', false)
        ->addSelect("
            CASE
                WHEN c.nom LIKE '7e%' THEN 1
                WHEN c.nom LIKE '8e%' THEN 2
                WHEN c.nom LIKE '1e%' THEN 3
                WHEN c.nom LIKE '2e%' THEN 4
                WHEN c.nom LIKE '3e%' THEN 5
                WHEN c.nom LIKE '4e%' THEN 6
                ELSE 99
            END AS HIDDEN ordre_classe
        ")
        ->orderBy('ordre_classe', 'ASC')
        ->addOrderBy('c.nom', 'ASC')
        ->addOrderBy('o.nom', 'ASC')
        ->getQuery()
        ->getResult();
}


//    /**
//     * @return Option[] Returns an array of Option objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Option
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
