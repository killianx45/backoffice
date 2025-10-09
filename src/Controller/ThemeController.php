<?php

namespace App\Controller;

use App\Entity\Theme;
use App\Service\ThemeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cms/theme')]
#[IsGranted('ROLE_DESIGNEUR')]
class ThemeController extends AbstractController
{
    public function __construct(
        private ThemeService $themeService
    ) {
    }

    #[Route('', name: 'app_theme_index')]
    public function index(): Response
    {
        $themes = $this->themeService->getAllThemes();
        $activeTheme = $this->themeService->getActiveTheme();

        return $this->render('theme/index.html.twig', [
            'themes' => $themes,
            'activeTheme' => $activeTheme,
        ]);
    }

    #[Route('/{id}/activate', name: 'app_theme_activate', methods: ['POST'])]
    public function activate(Theme $theme): Response
    {
        $this->themeService->setActiveTheme($theme);

        $this->addFlash('success', sprintf('Le thème "%s" a été activé avec succès.', $theme->getName()));

        return $this->redirectToRoute('app_theme_index');
    }

    #[Route('/{id}/preview', name: 'app_theme_preview')]
    public function preview(Theme $theme): Response
    {
        return $this->render('theme/preview.html.twig', [
            'theme' => $theme,
        ]);
    }
}
