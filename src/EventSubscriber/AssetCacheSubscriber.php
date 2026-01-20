<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

class AssetCacheSubscriber implements EventSubscriberInterface
{
    private const CACHE_MAX_AGE = 31536000; // 1 year in seconds
    private const CACHEABLE_EXTENSIONS = ['js', 'css', 'woff', 'woff2', 'ttf', 'otf', 'eot', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];

    public function __construct(
        private KernelInterface $kernel
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $path = $request->getPathInfo();

        // Handle /build/ assets (Webpack Encore versioned assets)
        if (str_starts_with($path, '/build/')) {
            $this->setImmutableCacheHeaders($response);
            return;
        }

        // Handle other static assets by extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, self::CACHEABLE_EXTENSIONS, true)) {
            $this->setImmutableCacheHeaders($response);
            return;
        }

        // For HTML responses in production, enable compression hint
        if ($this->kernel->getEnvironment() === 'prod' &&
            str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            $response->headers->set('Vary', 'Accept-Encoding');
        }
    }

    private function setImmutableCacheHeaders($response): void
    {
        $response->headers->set('Cache-Control', sprintf(
            'public, max-age=%d, immutable',
            self::CACHE_MAX_AGE
        ));

        // Set Expires header for older proxies
        $expires = new \DateTime();
        $expires->modify('+1 year');
        $response->headers->set('Expires', $expires->format('D, d M Y H:i:s') . ' GMT');
    }
}
