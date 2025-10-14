<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/user', name: 'api_user_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class UserApiController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer
    ) {}

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'avatar' => $user->getAvatar() ? '/uploads/avatars/' . $user->getAvatar() : null,
        ];

        return new JsonResponse($userData);
    }
}
