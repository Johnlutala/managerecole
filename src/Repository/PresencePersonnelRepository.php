<?php
namespace App\Repository;
use App\Entity\PresencePersonnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
/** @extends ServiceEntityRepository<PresencePersonnel> */
final class PresencePersonnelRepository extends ServiceEntityRepository { public function __construct(ManagerRegistry $registry) { parent::__construct($registry, PresencePersonnel::class); } }
