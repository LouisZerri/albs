<?php

namespace App\Repository;

use App\Entity\LineDiscussion;
use App\Entity\LineDiscussionReply;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LineDiscussionReply>
 */
class LineDiscussionReplyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LineDiscussionReply::class);
    }

    public function countMessagesToday(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function createQueryForDiscussionReplies(LineDiscussion $discussion): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->select('r', 'a')
            ->leftJoin('r.author', 'a')
            ->where('r.discussion = :discussion')
            ->setParameter('discussion', $discussion)
            ->orderBy('r.createdAt', 'ASC');
    }

    public function createQueryForRecentReplies(): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->select('r', 'a', 'd')
            ->leftJoin('r.author', 'a')
            ->leftJoin('r.discussion', 'd')
            ->orderBy('r.createdAt', 'DESC');
    }

    public function deleteAllByUser(User $user): int
    {
        return $this->createQueryBuilder('r')
            ->delete()
            ->where('r.author = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function findByAuthorWithDiscussion(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->select('r', 'd')
            ->leftJoin('r.discussion', 'd')
            ->where('r.author = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return LineDiscussionReply[] Returns an array of LineDiscussionReply objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?LineDiscussionReply
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
