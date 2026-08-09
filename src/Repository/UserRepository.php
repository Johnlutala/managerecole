<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[] Returns an array of User objects matching search and filters.
     */
    public function findBySearchAndFilters(?string $search, ?string $role, ?string $status): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.deleted = :deleted')
            ->setParameter('deleted', false)
            ->orderBy('u.createdAt', 'DESC');

        if ($status !== null && $status !== '') {
            $qb->andWhere('u.enabled = :enabled')
                ->setParameter('enabled', $status === '1');
        }

        if ($role !== null && $role !== '') {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%"' . $role . '"%');
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    'u.firstname LIKE :search',
                    'u.lastname LIKE :search',
                    'u.username LIKE :search',
                    'u.email LIKE :search'
                )
            )
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    public function countNotDeleted(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.deleted = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
