<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\CsvDataset;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    #[Route('/profile', name: 'update_profile', methods: ['POST'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $data = json_decode($request->getContent(), true);
            if (!empty($data['password'])) {
                if (strlen($data['password']) < 6) {
                    return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
                }
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Profil mis à jour avec succès',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'roles' => $user->getRoles(),
                    'isVerified' => $user->isVerified(),
                    'avatar' => $user->getAvatar() ? '/uploads/avatars/' . $user->getAvatar() : null,
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la mise à jour du profil'], 500);
        }
    }

    #[Route('/profile/avatar', name: 'update_avatar', methods: ['POST'])]
    public function updateAvatar(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $avatarFile = $request->files->get('avatar');

        if (!$avatarFile) {
            return new JsonResponse(['error' => 'Aucun fichier fourni'], 400);
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($avatarFile->getMimeType(), $allowedMimeTypes)) {
            return new JsonResponse(['error' => 'Format de fichier non supporté. Utilisez JPG, PNG ou WebP.'], 400);
        }

        if ($avatarFile->getSize() > 2 * 1024 * 1024) {
            return new JsonResponse(['error' => 'Le fichier est trop volumineux. Maximum 2MB.'], 400);
        }

        try {
            if ($user->getAvatar()) {
                $oldAvatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getAvatar();
                if (file_exists($oldAvatarPath)) {
                    unlink($oldAvatarPath);
                }
            }

            $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $avatarFile->guessExtension();
            $avatarsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';

            if (!is_dir($avatarsDirectory)) {
                mkdir($avatarsDirectory, 0750, true);
            }

            $avatarFile->move($avatarsDirectory, $newFilename);
            $user->setAvatar($newFilename);

            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Avatar mis à jour avec succès',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'roles' => $user->getRoles(),
                    'isVerified' => $user->isVerified(),
                    'avatar' => $user->getAvatar() ? '/uploads/avatars/' . $user->getAvatar() : null,
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de l\'upload de l\'avatar'], 500);
        }
    }

    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteAccount(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $csvDatasets = $entityManager->getRepository(CsvDataset::class)->findBy(['uploadedBy' => $user]);
            foreach ($csvDatasets as $dataset) {
                $csvFilePath = $this->getParameter('kernel.project_dir') . '/public/media/csv/' . $dataset->getFilename();
                if (file_exists($csvFilePath)) {
                    unlink($csvFilePath);
                }

                $entityManager->remove($dataset);
            }

            if ($user->getAvatar()) {
                $avatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getAvatar();
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                }
            }
            $entityManager->remove($user);
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Compte supprimé avec succès',
                'success' => true
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la suppression du compte : ' . $e->getMessage()
            ], 500);
        }
    }
}
