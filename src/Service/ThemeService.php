<?php

namespace App\Service;

use App\Entity\Theme;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;

class ThemeService
{
    public function __construct(
        private ThemeRepository $themeRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getActiveTheme(): ?Theme
    {
        return $this->themeRepository->findOneBy(['isActive' => true]);
    }

    public function getAllThemes(): array
    {
        return $this->themeRepository->findAll();
    }

    public function setActiveTheme(Theme $theme): void
    {
        $currentActive = $this->getActiveTheme();
        if ($currentActive && $currentActive->getId() !== $theme->getId()) {
            $currentActive->setIsActive(false);
            $this->entityManager->persist($currentActive);
        }

        $theme->setIsActive(true);
        $this->entityManager->persist($theme);
        $this->entityManager->flush();
    }

    public function getDefaultThemeConfiguration(): array
    {
        return [
            'layout' => [
                'articleWidth' => 'medium',
                'featuredImageSize' => 'large',
                'contentAlignment' => 'left',
                'spacing' => 'comfortable',
                'titlePosition' => 'above',
            ],
            'typography' => [
                'headingFont' => 'montserrat',
                'bodyFont' => 'darker-grotesque',
                'headingSize' => 'medium',
                'bodySize' => 'medium',
                'lineHeight' => 'normal',
            ],
            'colors' => [
                'primary' => '#171717',
                'secondary' => '#262626',
                'accent' => '#262626',
                'base100' => '#ffffff',
                'base200' => '#fafafa',
                'base300' => '#e8e8e8',
                'titleOverlay' => '#000000',
                'chartPalette' => [
                    '#a855f7',
                    '#9333ea',
                    '#7e22ce',
                    '#6b21a8',
                    '#581c87',
                ],
            ],
            'charts' => [
                'legendPosition' => 'top',
            ],
            'components' => [
                'cardStyle' => 'bordered',
                'buttonStyle' => 'outline',
                'borderRadius' => 'medium',
            ],
        ];
    }
}
