<?php

namespace App\Controller\Api;

use App\Service\ThemeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/theme')]
class ThemeApiController extends AbstractController
{
    public function __construct(
        private ThemeService $themeService,
        private TranslatorInterface $translator
    ) {}

    #[Route('/active', name: 'api_theme_active', methods: ['GET'])]
    public function getActiveTheme(): JsonResponse
    {
        $theme = $this->themeService->getActiveTheme();

        if (!$theme) {
            return $this->json([
                'error' => $this->translator->trans('api.theme.error.no_active'),
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
                'error' => $this->translator->trans('api.theme.error.not_found'),
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
