<?php

namespace App\Repository;

use App\Entity\AnneeScolaire;
use App\Entity\BulletinAnnuel;
use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Option;
use App\Entity\Section;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BulletinAnnuel>
 */
class BulletinAnnuelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BulletinAnnuel::class);
    }

    /**
     * @return BulletinAnnuel[]
     */
    public function findForFilters(
        ?Ecole $ecole,
        ?AnneeScolaire $anneeScolaire,
        ?Section $section = null,
        ?Classe $classe = null,
        ?Option $option = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('bulletin')
            ->innerJoin('bulletin.eleve', 'eleve')
            ->innerJoin('bulletin.classe', 'classe')
            ->orderBy('classe.nom', 'ASC')
            ->addOrderBy('eleve.nom', 'ASC')
            ->addOrderBy('eleve.postnom', 'ASC')
            ->addOrderBy('eleve.prenom', 'ASC');

        if ($ecole !== null) {
            $queryBuilder->andWhere('bulletin.ecole = :ecole')->setParameter('ecole', $ecole);
        }
        if ($anneeScolaire !== null) {
            $queryBuilder->andWhere('bulletin.anneeScolaire = :anneeScolaire')
                ->setParameter('anneeScolaire', $anneeScolaire);
        }
        if ($section !== null) {
            $queryBuilder->andWhere('classe.section = :section')->setParameter('section', $section);
        }
        if ($classe !== null) {
            $queryBuilder->andWhere('bulletin.classe = :classe')->setParameter('classe', $classe);
        }
        if ($option !== null) {
            $queryBuilder->andWhere('bulletin.option = :option')->setParameter('option', $option);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return BulletinAnnuel[]
     */
    public function findForClassAndYear(Classe $classe, AnneeScolaire $anneeScolaire, ?Option $option): array
    {
        return $this->findBy(
            ['classe' => $classe, 'anneeScolaire' => $anneeScolaire, 'option' => $option],
            ['pourcentage' => 'DESC']
        );
    }

    public function deleteForClassYearOption(
        Classe $classe,
        AnneeScolaire $anneeScolaire,
        ?Option $option
    ): void {
        foreach ($this->findBy([
            'classe' => $classe,
            'anneeScolaire' => $anneeScolaire,
            'option' => $option,
        ]) as $bulletin) {
            $this->getEntityManager()->remove($bulletin);
        }
    }

    public function deleteForEvaluationClassAndYear(
        Classe $classe,
        AnneeScolaire $anneeScolaire
    ): void {
        foreach ($this->findBy(['classe' => $classe, 'anneeScolaire' => $anneeScolaire]) as $bulletin) {
            $this->getEntityManager()->remove($bulletin);
        }
    }
}
