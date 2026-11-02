<?php

namespace App\Repository;

use App\Entity\AutorisationCotation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AutorisationCotation>
 */
class AutorisationCotationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutorisationCotation::class);
    }

    /**
     * @return array<string, bool>
     */
    public function autorisationsPourStructure(string $structure): array
    {
        $autorisations = [];
        foreach ($this->findBy(['structure' => $structure]) as $autorisation) {
            $autorisations[$autorisation->getPeriode()] = $autorisation->isAutorisee();
        }

        return $autorisations;
    }
}
