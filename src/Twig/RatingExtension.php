<?php

namespace App\Twig;

use App\Repository\RatingRepository;
use App\Entity\Article;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RatingExtension extends AbstractExtension
{
    public function __construct(
        private RatingRepository $ratingRepository
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('article_rating_stats', [$this, 'getArticleRatingStats']),
        ];
    }

    /**
     * Récupère les statistiques de notation pour un article
     */
    public function getArticleRatingStats(Article $article): array
    {
        return $this->ratingRepository->getArticleRatingStats($article);
    }
}
