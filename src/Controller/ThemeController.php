<?php

namespace App\Controller;

use App\Entity\Theme;
use App\Entity\Article;
use App\Form\ThemeType;
use App\Repository\ArticleRepository;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/cms/theme')]
#[IsGranted('ROLE_DESIGNEUR')]
class ThemeController extends AbstractController
{
    public function __construct(
        private ThemeService $themeService,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        private TranslatorInterface $translator,
        private ArticleRepository $articleRepository
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

    #[Route('/new', name: 'app_theme_new')]
    public function new(Request $request): Response
    {
        $theme = new Theme();
        $theme->setConfiguration($this->themeService->getDefaultThemeConfiguration());

        $form = $this->createForm(ThemeType::class, $theme);
        $form->get('configuration')->setData($theme->getConfiguration());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($theme->getSlug())) {
                $theme->setSlug(strtolower($this->slugger->slug($theme->getName())));
            }

            $configurationData = $form->get('configuration')->getData();
            $theme->setConfiguration($configurationData);

            if ($theme->isActive()) {
                $this->themeService->setActiveTheme($theme);
            } else {
                $this->entityManager->persist($theme);
                $this->entityManager->flush();
            }

            $this->addFlash('success', $this->translator->trans('theme.flash.created', ['%name%' => $theme->getName()]));

            return $this->redirectToRoute('app_theme_index');
        }

        return $this->render('theme/edit.html.twig', [
            'theme' => $theme,
            'form' => $form,
            'isNew' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_theme_edit')]
    public function edit(Request $request, Theme $theme): Response
    {
        $form = $this->createForm(ThemeType::class, $theme);
        $form->get('configuration')->setData($theme->getConfiguration());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $configurationData = $form->get('configuration')->getData();
            $theme->setConfiguration($configurationData);

            if ($theme->isActive()) {
                $currentActive = $this->themeService->getActiveTheme();
                if ($currentActive && $currentActive->getId() !== $theme->getId()) {
                    $currentActive->setIsActive(false);
                    $this->entityManager->persist($currentActive);
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('theme.flash.updated', ['%name%' => $theme->getName()]));

            return $this->redirectToRoute('app_theme_index');
        }

        return $this->render('theme/edit.html.twig', [
            'theme' => $theme,
            'form' => $form,
            'isNew' => false,
        ]);
    }

    #[Route('/{id}/activate', name: 'app_theme_activate', methods: ['POST'])]
    public function activate(Theme $theme): Response
    {
        $this->themeService->setActiveTheme($theme);

        $this->addFlash('success', $this->translator->trans('theme.flash.activated', ['%name%' => $theme->getName()]));

        return $this->redirectToRoute('app_theme_index');
    }

    #[Route('/{id}/preview', name: 'app_theme_preview')]
    public function preview(Request $request, Theme $theme): Response
    {
        $articleId = $request->query->get('article');

        if ($articleId) {
            $article = $this->articleRepository->find($articleId);
        } else {
            $article = $this->articleRepository->findOneBy([], ['createdAt' => 'DESC']);
        }

        $articles = $this->articleRepository->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('theme/preview.html.twig', [
            'theme' => $theme,
            'article' => $article,
            'articles' => $articles,
        ]);
    }
}
