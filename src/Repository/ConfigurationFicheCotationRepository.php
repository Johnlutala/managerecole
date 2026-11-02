<?php

namespace App\Repository;

use App\Entity\ConfigurationFicheCotation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigurationFicheCotation>
 */
class ConfigurationFicheCotationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigurationFicheCotation::class);
    }
}
