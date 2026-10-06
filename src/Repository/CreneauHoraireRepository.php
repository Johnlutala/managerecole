<?php

namespace App\Repository;

use App\Entity\CreneauHoraire;
use App\Entity\Horaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CreneauHoraire>
 */
class CreneauHoraireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreneauHoraire::class);
    }

    /**
     * @return CreneauHoraire[]
     */
    public function findForHoraire(Horaire $horaire): array
    {
        return $this->createQueryBuilder('creneau')
            ->addSelect('jour', 'heure', 'cours', 'professeur')
            ->join('creneau.jour', 'jour')
            ->join('creneau.heure', 'heure')
            ->leftJoin('creneau.cours', 'cours')
            ->leftJoin('creneau.professeur', 'professeur')
            ->andWhere('creneau.horaire = :horaire')
            ->setParameter('horaire', $horaire)
            ->orderBy('heure.ordre', 'ASC')
            ->addOrderBy('jour.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
