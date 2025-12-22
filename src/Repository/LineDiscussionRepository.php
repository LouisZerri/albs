<?php

namespace App\Repository;

use App\Entity\Line;
use App\Entity\LineDiscussion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LineDiscussion>
 */
class LineDiscussionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LineDiscussion::class);
    }

    /**
     * Récupère les discussions récentes avec author, line et replies en une seule requête
     */
    public function findRecentWithRelations(int $limit = 10): array
    {
        // Étape 1 : récupérer les IDs des discussions récentes
        $ids = $this->createQueryBuilder('d')
            ->select('d.id')
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($ids)) {
            return [];
        }

        // Étape 2 : charger les discussions avec toutes les relations
        return $this->createQueryBuilder('d')
            ->select('d', 'a', 'l', 'r')
            ->leftJoin('d.author', 'a')
            ->leftJoin('d.line', 'l')
            ->leftJoin('d.replies', 'r')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les discussions d'une ligne avec author et replies
     */
    public function findByLineOrdered(Line $line): array
    {
        // Étape 1 : récupérer les IDs
        $ids = $this->createQueryBuilder('d')
            ->select('d.id')
            ->where('d.line = :line')
            ->setParameter('line', $line)
            ->orderBy('d.isPinned', 'DESC')
            ->addOrderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($ids)) {
            return [];
        }

        // Étape 2 : charger avec relations
        return $this->createQueryBuilder('d')
            ->select('d', 'a', 'r')
            ->leftJoin('d.author', 'a')
            ->leftJoin('d.replies', 'r')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('d.isPinned', 'DESC')
            ->addOrderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avec toutes les relations chargées
     */
    public function search(string $query): array
    {
        // Étape 1 : recherche des IDs
        $ids = $this->createQueryBuilder('d')
            ->select('d.id')
            ->leftJoin('d.author', 'a')
            ->where('d.title LIKE :query')
            ->orWhere('d.content LIKE :query')
            ->orWhere('a.username LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($ids)) {
            return [];
        }

        // Étape 2 : charger avec relations
        return $this->createQueryBuilder('d')
            ->select('d', 'a', 'l', 'r')
            ->leftJoin('d.author', 'a')
            ->leftJoin('d.line', 'l')
            ->leftJoin('d.replies', 'r')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime toutes les discussions d'un utilisateur
     */
    public function deleteAllByUser(User $user): int
    {
        $discussions = $this->findBy(['author' => $user]);
        $count = count($discussions);

        foreach ($discussions as $discussion) {
            $this->getEntityManager()->remove($discussion);
        }

        $this->getEntityManager()->flush();

        return $count;
    }

    public function findByAuthorWithRelations(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('d')
            ->select('d', 'l', 'r')
            ->leftJoin('d.line', 'l')
            ->leftJoin('d.replies', 'r')
            ->where('d.author = :user')
            ->setParameter('user', $user)
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findWithRelations(int $id): ?LineDiscussion
    {
        return $this->createQueryBuilder('d')
            ->select('d', 'l', 'a', 'ab', 'i')
            ->leftJoin('d.line', 'l')
            ->leftJoin('d.author', 'a')
            ->leftJoin('a.badges', 'ab')
            ->leftJoin('d.images', 'i')
            ->where('d.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findGeneralOrdered(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.author', 'a')
            ->addSelect('a')
            ->leftJoin('d.replies', 'r')
            ->addSelect('r')
            ->where('d.line IS NULL')
            ->orderBy('d.isPinned', 'DESC')
            ->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
