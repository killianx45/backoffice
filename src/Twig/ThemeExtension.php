<?php

namespace App\Twig;

use App\Service\ThemeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThemeExtension extends AbstractExtension
{
    public function __construct(
        private ThemeService $themeService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('active_theme', [$this, 'getActiveTheme']),
        ];
    }

    public function getActiveTheme(): ?array
    {
        $theme = $this->themeService->getActiveTheme();

        if (!$theme) {
            return null;
        }

        return [
            'id' => $theme->getId(),
            'name' => $theme->getName(),
            'slug' => $theme->getSlug(),
            'configuration' => $theme->getConfiguration(),
        ];
    }
}
