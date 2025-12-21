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
        if (empty($postIds)) {
            return [];
        }

        return array_column(
            $this->createQueryBuilder('w')
                ->select('DISTINCT w.relatedPostId')
                ->where('w.relatedPostId IN (:ids)')
                ->setParameter('ids', $postIds)
                ->getQuery()
                ->getScalarResult(),
            'relatedPostId'
        );
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
