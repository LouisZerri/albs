<?php

namespace App\Repository;

use App\Entity\Badge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Badge>
 */
class BadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Badge::class);
    }

    public function findMostUnlockedBadges(int $limit = 10): array
    {
        return $this->createQueryBuilder('b')
            ->select('b.id, b.name, b.icon, COUNT(u.id) as unlockCount')
            ->leftJoin('b.users', 'u')
            ->groupBy('b.id, b.name, b.icon')
            ->orderBy('unlockCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Badge[] Returns an array of Badge objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Badge
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
