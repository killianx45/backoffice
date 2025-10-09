<?php

namespace App\DataFixtures;

use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ThemeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $themes = $this->getThemesData();

        foreach ($themes as $index => $themeData) {
            $theme = new Theme();
            $theme->setName($themeData['name']);
            $theme->setSlug($themeData['slug']);
            $theme->setDescription($themeData['description']);
            $theme->setConfiguration($themeData['configuration']);
            $theme->setIsActive($index === 0);

            $manager->persist($theme);
        }

        $manager->flush();
    }

    private function getThemesData(): array
    {
        return [
            [
                'name' => 'Minimaliste',
                'slug' => 'minimaliste',
                'description' => 'Design épuré et moderne avec une palette de gris neutres. Idéal pour un contenu élégant et professionnel.',
                'configuration' => [
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
                ],
            ],
            [
                'name' => 'Magazine',
                'slug' => 'magazine',
                'description' => 'Layout large et audacieux avec images proéminentes. Parfait pour un contenu visuel et impactant.',
                'configuration' => [
                    'layout' => [
                        'articleWidth' => 'wide',
                        'featuredImagePosition' => 'top',
                        'featuredImageSize' => 'full',
                        'contentAlignment' => 'left',
                        'spacing' => 'spacious',
                    ],
                    'typography' => [
                        'headingFont' => 'montserrat',
                        'bodyFont' => 'montserrat',
                        'headingSize' => 'large',
                        'bodySize' => 'large',
                        'lineHeight' => 'relaxed',
                    ],
                    'colors' => [
                        'primary' => 'hsl(0, 0%, 0%)',
                        'secondary' => 'hsl(0, 0%, 20%)',
                        'accent' => 'hsl(25, 95%, 53%)',
                        'base100' => 'hsl(0, 0%, 100%)',
                        'base200' => 'hsl(0, 0%, 97%)',
                        'base300' => 'hsl(0, 0%, 90%)',
                        'chartPalette' => [
                            'hsl(25, 95%, 53%)',
                            'hsl(45, 93%, 47%)',
                            'hsl(340, 82%, 52%)',
                            'hsl(291, 64%, 42%)',
                            'hsl(262, 52%, 47%)',
                        ],
                    ],
                    'charts' => [
                        'defaultType' => 'bar',
                        'colorScheme' => 'vibrant',
                        'gridStyle' => 'solid',
                        'legendPosition' => 'bottom',
                        'animation' => true,
                        'responsive' => true,
                    ],
                    'components' => [
                        'cardStyle' => 'shadow',
                        'buttonStyle' => 'solid',
                        'borderRadius' => 'small',
                    ],
                ],
            ],
            [
                'name' => 'Corporate',
                'slug' => 'corporate',
                'description' => 'Style professionnel et structuré avec des tons bleus. Idéal pour du contenu business et formel.',
                'configuration' => [
                    'layout' => [
                        'articleWidth' => 'medium',
                        'featuredImagePosition' => 'top',
                        'featuredImageSize' => 'medium',
                        'contentAlignment' => 'justify',
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
                        'primary' => 'hsl(210, 100%, 25%)',
                        'secondary' => 'hsl(210, 50%, 40%)',
                        'accent' => 'hsl(210, 100%, 50%)',
                        'base100' => 'hsl(0, 0%, 100%)',
                        'base200' => 'hsl(210, 20%, 98%)',
                        'base300' => 'hsl(210, 20%, 95%)',
                        'chartPalette' => [
                            'hsl(210, 100%, 50%)',
                            'hsl(200, 100%, 45%)',
                            'hsl(190, 90%, 40%)',
                            'hsl(180, 80%, 35%)',
                            'hsl(170, 70%, 30%)',
                        ],
                    ],
                    'charts' => [
                        'defaultType' => 'line',
                        'colorScheme' => 'blue',
                        'gridStyle' => 'dashed',
                        'legendPosition' => 'top',
                        'animation' => true,
                        'responsive' => true,
                    ],
                    'components' => [
                        'cardStyle' => 'bordered',
                        'buttonStyle' => 'solid',
                        'borderRadius' => 'small',
                    ],
                ],
            ],
            [
                'name' => 'Créatif',
                'slug' => 'creatif',
                'description' => 'Design audacieux avec couleurs vives et layout dynamique. Pour un contenu créatif et original.',
                'configuration' => [
                    'layout' => [
                        'articleWidth' => 'narrow',
                        'featuredImagePosition' => 'top',
                        'featuredImageSize' => 'large',
                        'contentAlignment' => 'left',
                        'spacing' => 'spacious',
                    ],
                    'typography' => [
                        'headingFont' => 'montserrat',
                        'bodyFont' => 'darker-grotesque',
                        'headingSize' => 'large',
                        'bodySize' => 'large',
                        'lineHeight' => 'relaxed',
                    ],
                    'colors' => [
                        'primary' => 'hsl(340, 82%, 52%)',
                        'secondary' => 'hsl(291, 64%, 42%)',
                        'accent' => 'hsl(45, 93%, 47%)',
                        'base100' => 'hsl(0, 0%, 100%)',
                        'base200' => 'hsl(0, 0%, 99%)',
                        'base300' => 'hsl(0, 0%, 96%)',
                        'chartPalette' => [
                            'hsl(340, 82%, 52%)',
                            'hsl(25, 95%, 53%)',
                            'hsl(45, 93%, 47%)',
                            'hsl(160, 84%, 39%)',
                            'hsl(262, 52%, 47%)',
                        ],
                    ],
                    'charts' => [
                        'defaultType' => 'line',
                        'colorScheme' => 'rainbow',
                        'gridStyle' => 'dotted',
                        'legendPosition' => 'right',
                        'animation' => true,
                        'responsive' => true,
                    ],
                    'components' => [
                        'cardStyle' => 'shadow',
                        'buttonStyle' => 'solid',
                        'borderRadius' => 'large',
                    ],
                ],
            ],
        ];
    }
}
