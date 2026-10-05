<?php

namespace App\Repository;

use App\Entity\Cours;
use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Professeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cours>
 */
class CoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cours::class);
    }

    /**
     * @return Cours[]
     */
    public function findForEcole(
        Ecole $ecole,
        ?Classe $classe = null,
        ?Professeur $professeur = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('cours')
            ->innerJoin('cours.classe', 'classe')
            ->andWhere('classe.ecole = :ecole')
            ->setParameter('ecole', $ecole);

        /*
     * Ordre scolaire :
     * 7e → 8e → 1e → 2e → 3e → 4e
     */
        $queryBuilder
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
            ->addOrderBy('cours.nom', 'ASC');

        if ($classe) {
            $queryBuilder
                ->andWhere('cours.classe = :classe')
                ->setParameter('classe', $classe);
        }

        if ($professeur) {
            $queryBuilder
                ->andWhere('cours.professeur = :professeur')
                ->setParameter('professeur', $professeur);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    //    /**
    //     * @return Cours[] Returns an array of Cours objects
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

    //    public function findOneBySomeField($value): ?Cours
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
