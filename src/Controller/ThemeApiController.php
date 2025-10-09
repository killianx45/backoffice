<?php

namespace App\Controller;

use App\Service\ThemeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/theme')]
class ThemeApiController extends AbstractController
{
    public function __construct(
        private ThemeService $themeService
    ) {
    }

    #[Route('/active', name: 'api_theme_active', methods: ['GET'])]
    public function getActiveTheme(): JsonResponse
    {
        $theme = $this->themeService->getActiveTheme();

        if (!$theme) {
            return $this->json([
                'error' => 'No active theme found',
            ], 404);
        }

        return $this->json([
            'id' => $theme->getId(),
            'name' => $theme->getName(),
            'slug' => $theme->getSlug(),
            'description' => $theme->getDescription(),
            'configuration' => $theme->getConfiguration(),
        ]);
    }

    #[Route('/{id}', name: 'api_theme_get', methods: ['GET'])]
    public function getTheme(int $id): JsonResponse
    {
        $theme = $this->themeService->getAllThemes();
        $theme = array_filter($theme, fn($t) => $t->getId() === $id);

        if (empty($theme)) {
            return $this->json([
                'error' => 'Theme not found',
            ], 404);
        }

        $theme = reset($theme);

        return $this->json([
            'id' => $theme->getId(),
            'name' => $theme->getName(),
            'slug' => $theme->getSlug(),
            'description' => $theme->getDescription(),
            'configuration' => $theme->getConfiguration(),
        ]);
    }
}
