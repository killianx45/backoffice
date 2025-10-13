<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'addSecurityHeaders',
        ];
    }

    public function addSecurityHeaders(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $headers = $response->headers;

        // Protection contre les attaques de type MIME sniffing
        $headers->set('X-Content-Type-Options', 'nosniff');

        // Protection XSS intégrée du navigateur
        $headers->set('X-XSS-Protection', '1; mode=block');

        // Protection contre le clickjacking (déjà géré par nelmio_security mais en backup)
        if (!$headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        // Politique de référrer
        if (!$headers->has('Referrer-Policy')) {
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        // Permissions Policy (ex-Feature Policy)
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Protection contre le téléchargement de contenu malveillant
        if (strpos($event->getRequest()->getPathInfo(), '/media/') !== false) {
            $headers->set('X-Download-Options', 'noopen');
        }
    }
}
