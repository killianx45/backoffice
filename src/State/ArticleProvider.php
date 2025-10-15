<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Service\ArticleRatingService;

class ArticleProvider implements ProviderInterface
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private ArticleRatingService $articleRatingService
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $class = $operation->getClass();

        if ($class !== Article::class) {
            return null;
        }

        if ($operation instanceof \ApiPlatform\Metadata\GetCollection) {
            $articles = $this->articleRepository->findAll();
            return $this->articleRatingService->enrichCollectionWithRatingStats($articles);
        }

        if (isset($uriVariables['id'])) {
            $article = $this->articleRepository->find($uriVariables['id']);
            if (!$article) {
                return null;
            }
            return $this->articleRatingService->enrichWithRatingStats($article);
        }

        return null;
    }
}
