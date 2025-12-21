<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserStation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserStation>
 */
class UserStationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserStation::class);
    }

    public function findByUserWithStations(User $user): array
    {
        return $this->createQueryBuilder('us')
            ->addSelect('s')
            ->innerJoin('us.station', 's')
            ->where('us.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findMostVisitedStations(int $limit = 10): array
    {
        return $this->createQueryBuilder('us')
            ->select('s.id, s.name, l.number as lineNumber, l.color as lineColor, COUNT(us.id) as visitCount')
            ->join('us.station', 's')
            ->join('s.line', 'l')
            ->where('us.stopped = true')
            ->groupBy('s.id, s.name, l.number, l.color')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return UserStation[] Returns an array of UserStation objects
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

    //    public function findOneBySomeField($value): ?UserStation
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
