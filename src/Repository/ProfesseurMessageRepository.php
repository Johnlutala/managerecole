<?php

namespace App\Repository;

use App\Entity\MessageProfesseur;
use App\Entity\Professeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageProfesseur>
 */
class ProfesseurMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageProfesseur::class);
    }

    public function countUnreadFor(Professeur $professeur): int
    {
        return (int) $this->createQueryBuilder('message')
            ->select('COUNT(message.id)')
            ->andWhere('message.professeur = :professeur')
            ->andWhere('message.luLe IS NULL')
            ->setParameter('professeur', $professeur)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return MessageProfesseur[]
     */
    public function findUnreadLatestFor(Professeur $professeur, int $limit = 5): array
    {
        return $this->findBy(
            ['professeur' => $professeur, 'luLe' => null],
            ['envoyeLe' => 'DESC'],
            $limit
        );
    }
}
