<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\Rating;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RatingApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RatingRepository $ratingRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    /**
     * Noter un article (POST /api/articles/{id}/rating)
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function rateArticle(Article $article, Request $request): JsonResponse
    {
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (!isset($data['stars']) || !is_int($data['stars'])) {
            return new JsonResponse(['error' => 'Le champ "stars" est requis et doit être un entier'], Response::HTTP_BAD_REQUEST);
        }

        $stars = (int) $data['stars'];

        if ($stars < 1 || $stars > 5) {
            return new JsonResponse(['error' => 'La note doit être comprise entre 1 et 5 étoiles'], Response::HTTP_BAD_REQUEST);
        }

        $existingRating = $this->ratingRepository->findUserRatingForArticle($user, $article);

        if ($existingRating) {
            $existingRating->setStars($stars);
            $rating = $existingRating;
        } else {
            $rating = new Rating();
            $rating->setUser($user)
                ->setArticle($article)
                ->setStars($stars);
        }

        $errors = $this->validator->validate($rating);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($rating);
        $this->entityManager->flush();
        $stats = $this->ratingRepository->getArticleRatingStats($article);

        return new JsonResponse([
            'message' => $existingRating ? 'Note mise à jour avec succès' : 'Note ajoutée avec succès',
            'rating' => [
                'id' => $rating->getId(),
                'stars' => $rating->getStars(),
                'createdAt' => $rating->getCreatedAt()->format('c'),
                'updatedAt' => $rating->getUpdatedAt()->format('c')
            ],
            'stats' => [
                'average' => round($stats['average'], 2),
                'count' => $stats['count']
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Récupérer la note d'un utilisateur pour un article (GET /api/articles/{id}/rating)
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getUserRating(Article $article): JsonResponse
    {
        $user = $this->getUser();
        $rating = $this->ratingRepository->findUserRatingForArticle($user, $article);

        if (!$rating) {
            return new JsonResponse([
                'userRating' => null,
                'hasRated' => false
            ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'userRating' => [
                'id' => $rating->getId(),
                'stars' => $rating->getStars(),
                'createdAt' => $rating->getCreatedAt()->format('c'),
                'updatedAt' => $rating->getUpdatedAt()->format('c')
            ],
            'hasRated' => true
        ], Response::HTTP_OK);
    }

    /**
     * Récupérer les statistiques de notation d'un article (GET /api/articles/{id}/rating/stats)
     */
    public function getArticleRatingStats(Article $article): JsonResponse
    {
        $stats = $this->ratingRepository->getArticleRatingStats($article);
        $distribution = $this->ratingRepository->getRatingDistributionForArticle($article);

        return new JsonResponse([
            'stats' => [
                'average' => round($stats['average'], 2),
                'count' => $stats['count']
            ],
            'distribution' => $distribution
        ], Response::HTTP_OK);
    }

    /**
     * Supprimer sa note d'un article (DELETE /api/articles/{id}/rating)
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteRating(Article $article): JsonResponse
    {
        $user = $this->getUser();
        $rating = $this->ratingRepository->findUserRatingForArticle($user, $article);

        if (!$rating) {
            return new JsonResponse(['error' => 'Aucune note trouvée pour cet article'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($rating);
        $this->entityManager->flush();
        $stats = $this->ratingRepository->getArticleRatingStats($article);

        return new JsonResponse([
            'message' => 'Note supprimée avec succès',
            'stats' => [
                'average' => round($stats['average'], 2),
                'count' => $stats['count']
            ]
        ], Response::HTTP_OK);
    }
}
