<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Theme;
use App\Repository\ArticleRepository;
use App\Repository\ThemeRepository;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(event: SitemapPopulateEvent::class, method: 'populate')]
class SitemapPopulator
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private ThemeRepository $themeRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function populate(SitemapPopulateEvent $event): void
    {
        $urlContainer = $event->getUrlContainer();
        $section = $event->getSection();

        // Only generate for default section
        if ($section !== null && $section !== 'default') {
            return;
        }

        $this->registerArticles($urlContainer);
        $this->registerThemes($urlContainer);
        $this->registerStaticPages($urlContainer);
    }

    private function registerArticles(UrlContainerInterface $urls): void
    {
        $articles = $this->articleRepository->findAll();

        foreach ($articles as $article) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'app_article_show',
                        ['id' => $article->getId()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    $article->getCreatedAt(),
                    UrlConcrete::CHANGEFREQ_WEEKLY,
                    0.7
                ),
                'default'
            );
        }
    }

    private function registerThemes(UrlContainerInterface $urls): void
    {
        $themes = $this->themeRepository->findAll();

        foreach ($themes as $theme) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'app_theme_preview',
                        ['id' => $theme->getId()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    new \DateTimeImmutable(),
                    UrlConcrete::CHANGEFREQ_MONTHLY,
                    0.5
                ),
                'default'
            );
        }
    }

    private function registerStaticPages(UrlContainerInterface $urls): void
    {
        // Add static pages if they should be indexed
        // For a backoffice, you might want to skip this or only add public pages

        // Example: Article index page
        $urls->addUrl(
            new UrlConcrete(
                $this->urlGenerator->generate(
                    'app_article_index',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                new \DateTimeImmutable(),
                UrlConcrete::CHANGEFREQ_DAILY,
                0.8
            ),
            'default'
        );
    }
}
