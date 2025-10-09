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
                'featuredImagePosition' => 'top',
                'featuredImageSize' => 'large',
                'contentAlignment' => 'left',
                'spacing' => 'comfortable',
            ],
            'typography' => [
                'headingFont' => 'montserrat',
                'bodyFont' => 'darker-grotesque',
                'headingSize' => 'medium',
                'bodySize' => 'medium',
                'lineHeight' => 'normal',
            ],
            'colors' => [
                'primary' => 'hsl(0, 0%, 9%)',
                'secondary' => 'hsl(0, 0%, 15%)',
                'accent' => 'hsl(0, 0%, 15%)',
                'base100' => 'hsl(0, 0%, 100%)',
                'base200' => 'hsl(0, 0%, 98%)',
                'base300' => 'hsl(0, 0%, 91%)',
                'chartPalette' => [
                    'hsl(276, 72%, 60%)',
                    'hsl(276, 60%, 50%)',
                    'hsl(276, 50%, 45%)',
                    'hsl(276, 45%, 40%)',
                    'hsl(276, 40%, 35%)',
                ],
            ],
            'charts' => [
                'defaultType' => 'bar',
                'colorScheme' => 'purple',
                'gridStyle' => 'solid',
                'legendPosition' => 'top',
                'animation' => true,
                'responsive' => true,
            ],
            'components' => [
                'cardStyle' => 'bordered',
                'buttonStyle' => 'outline',
                'borderRadius' => 'medium',
            ],
        ];
    }
}
