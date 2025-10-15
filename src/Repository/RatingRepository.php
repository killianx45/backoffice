<?php

namespace App\Repository;

use App\Entity\Rating;
use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    /**
     * Trouve la note d'un utilisateur pour un article spécifique
     */
    public function findUserRatingForArticle(User $user, Article $article): ?Rating
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.article = :article')
            ->setParameter('user', $user)
            ->setParameter('article', $article)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Calcule la moyenne des notes pour un article
     */
    public function getAverageRatingForArticle(Article $article): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.stars) as average')
            ->andWhere('r.article = :article')
            ->setParameter('article', $article)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }

    /**
     * Compte le nombre total de notes pour un article
     */
    public function getRatingCountForArticle(Article $article): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.article = :article')
            ->setParameter('article', $article)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Obtient les statistiques complètes de notation pour un article
     */
    public function getArticleRatingStats(Article $article): array
    {
        $stats = $this->createQueryBuilder('r')
            ->select('AVG(r.stars) as average, COUNT(r.id) as count')
            ->andWhere('r.article = :article')
            ->setParameter('article', $article)
            ->getQuery()
            ->getSingleResult();

        return [
            'average' => $stats['average'] ? (float) $stats['average'] : 0,
            'count' => (int) $stats['count']
        ];
    }

    /**
     * Obtient la distribution des notes pour un article (1-5 étoiles)
     */
    public function getRatingDistributionForArticle(Article $article): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.stars, COUNT(r.id) as count')
            ->andWhere('r.article = :article')
            ->setParameter('article', $article)
            ->groupBy('r.stars')
            ->orderBy('r.stars', 'ASC')
            ->getQuery()
            ->getResult();

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($results as $result) {
            $distribution[$result['stars']] = (int) $result['count'];
        }

        return $distribution;
    }
}
