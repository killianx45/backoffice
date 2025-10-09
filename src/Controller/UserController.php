<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route('', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/{id}/update-roles', name: 'app_user_update_roles', methods: ['POST'])]
    public function updateRoles(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $roles = $request->request->all('roles') ?? [];

        // Filter to keep only valid roles
        $validRoles = ['ROLE_AUTEUR', 'ROLE_EDITEUR', 'ROLE_FOURNISSEUR', 'ROLE_DESIGNEUR', 'ROLE_ADMIN'];
        $filteredRoles = array_intersect($roles, $validRoles);

        $user->setRoles($filteredRoles);
        $entityManager->flush();

        $this->addFlash('success', 'Les rôles de l\'utilisateur ont été mis à jour avec succès.');

        return $this->redirectToRoute('app_user_index');
    }
}
