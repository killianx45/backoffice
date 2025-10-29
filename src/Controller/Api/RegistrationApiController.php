<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationApiController extends AbstractController
{
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        EmailVerifier $emailVerifier,
        TranslatorInterface $translator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Données JSON invalides'], 400);
        }

        $requiredFields = ['nom', 'prenom', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return new JsonResponse(['error' => "Le champ '$field' est requis"], 400);
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Format d\'email invalide'], 400);
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Un compte avec cet email existe déjà'], 409);
        }

        $user = new User();
        $user->setNom(trim($data['nom']));
        $user->setPrenom(trim($data['prenom']));
        $user->setEmail(trim($data['email']));

        if (strlen($data['password']) < 6) {
            return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
        }

        $user->setPassword($userPasswordHasher->hashPassword($user, $data['password']));
        $user->setRoles([]);
        $user->setIsVerified(false);

        $violations = $validator->validate($user);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            return new JsonResponse(['error' => 'Erreur de validation', 'details' => $errors], 400);
        }

        try {
            $entityManager->persist($user);
            $entityManager->flush();

            // Envoi de l'email de vérification
            $emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->to(new Address((string) $user->getEmail(), $user->getPrenom() . ' ' . $user->getNom()))
                    ->subject($translator->trans('registration.email.confirmation_subject'))
                    ->htmlTemplate('email/verification.html.twig')
            );

            return new JsonResponse([
                'message' => 'Compte créé avec succès. Un email de vérification vous a été envoyé.',
                'requiresVerification' => true,
                'user' => [
                    'id' => $user->getId(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'isVerified' => $user->isVerified()
                ]
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la création du compte: ' . $e->getMessage()], 500);
        }
    }
}
