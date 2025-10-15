<?php

namespace App\Service;

use App\Entity\Article;
use App\Repository\RatingRepository;

class ArticleRatingService
{
    public function __construct(
        private RatingRepository $ratingRepository
    ) {}

    /**
     * Enrichit un article avec ses statistiques de notation
     */
    public function enrichWithRatingStats(Article $article): Article
    {
        $stats = $this->ratingRepository->getArticleRatingStats($article);

        $article->setAverageRating($stats['average'] > 0 ? round($stats['average'], 2) : null);
        $article->setRatingsCount($stats['count']);

        return $article;
    }

    /**
     * Enrichit une collection d'articles avec leurs statistiques de notation
     */
    public function enrichCollectionWithRatingStats(array $articles): array
    {
        foreach ($articles as $article) {
            $this->enrichWithRatingStats($article);
        }

        return $articles;
    }
}
