<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailVerificationSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_ROUTES = [
        'app_login',
        'app_logout',
        'app_verify_email',
        'app_email_verification_pending',
        'app_resend_verification_email',
        'app_dashboard', // Permettre l'accès au dashboard pour afficher la bannière de vérification
    ];

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité plus faible pour s'exécuter APRÈS le FirewallListener (priorité 8)
            KernelEvents::REQUEST => ['onKernelRequest', 7],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');

        if ($this->isRouteAllowed($routeName)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            return;
        }

        /** @var User $user */
        $user = $token->getUser();

        $freshUser = $this->userRepository->find($user->getId());

        if (!$freshUser) {
            return;
        }

        if (!$freshUser->isVerified()) {
            $session = $this->requestStack->getSession();

            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add(
                    'warning',
                    $this->translator->trans('registration.flash.email_not_verified')
                );
            }

            $redirectUrl = $this->urlGenerator->generate('app_email_verification_pending');
            $response = new RedirectResponse($redirectUrl);
            $event->setResponse($response);
        }
    }

    private function isRouteAllowed(?string $routeName): bool
    {
        if ($routeName === null) {
            return true;
        }

        if (in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return true;
        }

        // Routes système Symfony (profiler, etc.)
        if (str_starts_with($routeName, '_')) {
            return true;
        }

        // Routes API (exemptées de la vérification d'email)
        if (str_starts_with($routeName, 'api_') || str_starts_with($routeName, 'app_api_')) {
            return true;
        }

        return false;
    }
}
