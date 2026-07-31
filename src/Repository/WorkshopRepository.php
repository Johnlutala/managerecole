<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Workshop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Workshop>
 */
class WorkshopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workshop::class);
    }

    /**
     * @return Workshop[] Returns an array of Workshop objects
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.enabled = :enabled')
            ->setParameter('enabled', true)
            ->andWhere('w.deleted = :deleted')
            ->setParameter('deleted', false)
            ->orderBy('w.id', 'ASC')
            // ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Workshop[] Returns an array of Workshop objects
     */
    public function findActiveByUser(User $user, ?string $keyword): array
    {

        if (!$keyword) {
            return $this->createQueryBuilder('w')
                ->setParameter('enabled', true)
                ->andWhere('w.enabled = :enabled')
                ->setParameter('enabled', true)
                ->andWhere('w.deleted = :deleted')
                ->setParameter('deleted', false)
                ->andWhere('w.createdBy = :user')
                ->setParameter('user', $user)
                ->orderBy('w.id', 'ASC')
                // ->setMaxResults(10)
                ->getQuery()
                ->getResult()
            ;
        }

        return $this->createQueryBuilder('w')
            ->andWhere('w.name LIKE :name')
            ->setParameter('name', '%' . $keyword . '%')
            ->andWhere('w.enabled = :enabled')
            ->setParameter('enabled', true)
            ->andWhere('w.deleted = :deleted')
            ->setParameter('deleted', false)
            ->andWhere('w.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('w.id', 'ASC')
            // ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Workshop[] Returns an array of Workshop objects
     */
    public function findLatestByUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.enabled = :enabled')
            ->setParameter('enabled', true)
            ->andWhere('w.deleted = :deleted')
            ->setParameter('deleted', false)
            ->andWhere('w.isEnded = :isEnded')
            ->setParameter('isEnded', false)
            ->andWhere('w.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('w.createdAt', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult()
        ;
    }

   public function findAllNotDeleted(): array
{
    return $this->createQueryBuilder('w')
        ->andWhere('w.deleted = false')
        ->orderBy('w.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
}

    public function findByStatus(?bool $isEnded): array
    {
        $qb = $this->createQueryBuilder('w');

        if ($isEnded !== null) {
            $qb->andWhere('w.isEnded = :status')
              ->andWhere('w.deleted = false')
                ->setParameter('status', $isEnded);
        }

        return $qb
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Workshop[] Returns an array of Workshop objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('w.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Workshop
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
