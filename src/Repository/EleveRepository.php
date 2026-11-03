<?php

namespace App\Repository;

use App\Entity\Classe;
use App\Entity\Eleve;
use App\Entity\Option;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Eleve>
 */
class EleveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Eleve::class);
    }

    public function countActiveForClasseAndOption(Classe $classe, ?Option $option = null): int
    {
        $queryBuilder = $this->createQueryBuilder('eleve')
            ->select('COUNT(eleve.id)')
            ->andWhere('eleve.classe = :classe')
            ->andWhere('eleve.statut = :active')
            ->andWhere('eleve.deleted = :notDeleted')
            ->setParameter('classe', $classe)
            ->setParameter('active', true)
            ->setParameter('notDeleted', false);

        if ($option !== null) {
            $queryBuilder->andWhere('eleve.option = :option')->setParameter('option', $option);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

//    /**
//     * @return Eleve[] Returns an array of Eleve objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Eleve
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
