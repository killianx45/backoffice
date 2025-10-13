<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ABONNE')]
final class EmailVerificationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
    ) {}

    #[Route('/email/verification-pending', name: 'app_email_verification_pending')]
    public function pending(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Si déjà vérifié, rediriger vers le dashboard
        if ($user->isVerified()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('email/verification_pending.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/email/resend-verification', name: 'app_resend_verification_email', methods: ['POST'])]
    public function resend(TranslatorInterface $translator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isVerified()) {
            $this->addFlash('info', $translator->trans('registration.flash.already_verified'));
            return $this->redirectToRoute('app_dashboard');
        }

        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->to((string) $user->getEmail())
                ->subject($translator->trans('registration.email.confirmation_subject'))
                ->htmlTemplate('email/verification.html.twig')
        );

        $this->addFlash('success', $translator->trans('registration.flash.verification_resent'));

        return $this->redirectToRoute('app_email_verification_pending');
    }
}
