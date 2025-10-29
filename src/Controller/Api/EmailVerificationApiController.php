<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

#[Route('/api')]
class EmailVerificationApiController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Vérifie l'email de l'utilisateur via le token dans l'URL
     */
    #[Route('/verify-email', name: 'app_api_verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $userId = $request->query->get('id');

        if (!$userId) {
            return new JsonResponse([
                'success' => false,
                'error' => 'ID utilisateur manquant'
            ], 400);
        }

        $user = $this->userRepository->find($userId);

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Utilisateur non trouvé'
            ], 404);
        }

        if ($user->isVerified()) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Votre email est déjà vérifié',
                'alreadyVerified' => true
            ]);
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);

            return new JsonResponse([
                'success' => true,
                'message' => 'Votre email a été vérifié avec succès ! Vous pouvez maintenant vous connecter.',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'isVerified' => $user->isVerified()
                ]
            ]);
        } catch (VerifyEmailExceptionInterface $exception) {
            return new JsonResponse([
                'success' => false,
                'error' => $this->translator->trans($exception->getReason(), [], 'VerifyEmailBundle')
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Une erreur est survenue lors de la vérification'
            ], 500);
        }
    }

    /**
     * Renvoie l'email de vérification
     */
    #[Route('/resend-verification', name: 'app_api_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || empty($data['email'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Email requis'
            ], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            // Pour des raisons de sécurité, on ne révèle pas si l'email existe ou non
            return new JsonResponse([
                'success' => true,
                'message' => 'Si cet email existe dans notre système, un email de vérification a été envoyé.'
            ]);
        }

        if ($user->isVerified()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Cet email est déjà vérifié'
            ], 400);
        }

        try {
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->to(new Address((string) $user->getEmail(), $user->getPrenom() . ' ' . $user->getNom()))
                    ->subject($this->translator->trans('registration.email.confirmation_subject'))
                    ->htmlTemplate('email/verification.html.twig')
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Un nouvel email de vérification a été envoyé'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de l\'envoi de l\'email'
            ], 500);
        }
    }
}
