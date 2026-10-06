<?php

namespace App\Repository;

use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Horaire;
use App\Entity\Option;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Horaire>
 */
class HoraireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Horaire::class);
    }

    public function findForSelection(Classe $classe, Ecole $ecole, ?Option $option): ?Horaire
    {
        $queryBuilder = $this->createQueryBuilder('horaire')
            ->andWhere('horaire.classe = :classe')
            ->andWhere('horaire.ecole = :ecole')
            ->andWhere('horaire.option ' . ($option === null ? 'IS NULL' : '= :option'))
            ->setParameter('classe', $classe)
            ->setParameter('ecole', $ecole);

        if ($option !== null) {
            $queryBuilder->setParameter('option', $option);
        }

        return $queryBuilder->getQuery()
            ->getOneOrNullResult();
    }
}
