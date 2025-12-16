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
