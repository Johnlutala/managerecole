<?php

namespace App\Repository;

use App\Entity\PreparationCours;
use App\Entity\Professeur;
use App\Entity\Cours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PreparationCours>
 */
class PreparationCoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreparationCours::class);
    }

    /** @return PreparationCours[] */
    public function findForProfesseur(Professeur $professeur): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.professeur = :professeur')
            ->andWhere('p.deleted = false')
            ->setParameter('professeur', $professeur)
            ->orderBy('p.numero', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return PreparationCours[] */
    public function findSoumises(): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.cours', 'c')->addSelect('c')
            ->innerJoin('c.classe', 'cl')->addSelect('cl')
            ->innerJoin('cl.ecole', 'e')->addSelect('e')
            ->innerJoin('p.professeur', 'prof')->addSelect('prof')
            ->andWhere('p.statut = :statut')
            ->andWhere('p.deleted = false')
            ->setParameter('statut', PreparationCours::SOUMISE)
            ->orderBy('p.soumiseLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function prochainNumero(Professeur $professeur, Cours $cours): int
    {
        $dernierNumero = $this->createQueryBuilder('p')
            ->select('MAX(p.numero)')
            ->andWhere('p.professeur = :professeur')
            ->andWhere('p.cours = :cours')
            ->setParameter('professeur', $professeur)
            ->setParameter('cours', $cours)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $dernierNumero) + 1;
    }

    public function countSoumises(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.statut = :statut')
            ->andWhere('p.deleted = false')
            ->setParameter('statut', PreparationCours::SOUMISE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return PreparationCours[] */
    public function findForCours(Cours $cours, ?Professeur $professeur = null): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->andWhere('p.cours = :cours')
            ->andWhere('p.deleted = false')
            ->setParameter('cours', $cours)
            ->orderBy('p.numero', 'ASC');

        if ($professeur !== null) {
            $queryBuilder
                ->andWhere('p.professeur = :professeur')
                ->setParameter('professeur', $professeur);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
