<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

#[AsEventListener(event: LoginSuccessEvent::class)]
class BackofficeAccessListener
{
    public function __construct(
        private Security $security,
        private RouterInterface $router,
        private RequestStack $requestStack
    ) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        $firewallName = $event->getFirewallName();
        if ($firewallName !== 'main') {
            return;
        }

        $user = $event->getUser();
        $roles = $user->getRoles();
        $hasAdminRole = false;
        $allowedRoles = ['ROLE_AUTEUR', 'ROLE_EDITEUR', 'ROLE_DESIGNEUR', 'ROLE_FOURNISSEUR', 'ROLE_ADMIN'];

        foreach ($roles as $role) {
            if (in_array($role, $allowedRoles)) {
                $hasAdminRole = true;
                break;
            }
        }
        if (!$hasAdminRole) {
            $this->security->logout(false);
            $session = $this->requestStack->getSession();
            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add('error', 'Accès refusé. Seuls les utilisateurs avec des privilèges administratifs peuvent accéder au backoffice.');
            }
            $response = new RedirectResponse($this->router->generate('app_login'));
            $event->setResponse($response);
        }
    }
}
