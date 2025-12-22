<?php

namespace App\Repository;

use App\Entity\Warning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Warning>
 */
class WarningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Warning::class);
    }

    public function findWarnedPostIds(array $postIds): array
    {
        $result = $this->createQueryBuilder('w')
            ->select('w.relatedPostId')
            ->where('w.relatedPostId IN (:postIds)')
            ->setParameter('postIds', $postIds)
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'relatedPostId');
    }

    public function findLatestWithRelations(int $limit = 20): array
    {
        return $this->createQueryBuilder('w')
            ->select('w', 'u', 'm')
            ->leftJoin('w.user', 'u')
            ->leftJoin('w.moderator', 'm')
            ->orderBy('w.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Warning[] Returns an array of Warning objects
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

    //    public function findOneBySomeField($value): ?Warning
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
