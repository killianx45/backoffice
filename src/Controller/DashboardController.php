<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ABONNE')]
final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(ArticleRepository $articleRepository): Response
    {
        // Récupérer les 5 derniers articles
        $recentArticles = $articleRepository->findRecent(5);

        // Déterminer les actions disponibles selon les rôles
        $quickActions = $this->getQuickActions();

        return $this->render('dashboard/index.html.twig', [
            'recent_articles' => $recentArticles,
            'quick_actions' => $quickActions,
        ]);
    }

    private function getQuickActions(): array
    {
        $actions = [];

        // Articles - pour Auteurs, Éditeurs et Admins
        if ($this->isGranted('ROLE_AUTEUR') || $this->isGranted('ROLE_EDITEUR') || $this->isGranted('ROLE_ADMIN')) {
            $actions[] = [
                'title' => 'dashboard.quick_actions.articles.title',
                'description' => 'dashboard.quick_actions.articles.description',
                'icon' => 'article',
                'route' => 'app_article_index',
                'color' => 'primary',
            ];

            $actions[] = [
                'title' => 'dashboard.quick_actions.media.title',
                'description' => 'dashboard.quick_actions.media.description',
                'icon' => 'image',
                'route' => 'app_media_index',
                'color' => 'secondary',
            ];
        }

        // CSV - pour Fournisseurs et Admins
        if ($this->isGranted('ROLE_FOURNISSEUR') || $this->isGranted('ROLE_ADMIN')) {
            $actions[] = [
                'title' => 'dashboard.quick_actions.csv.title',
                'description' => 'dashboard.quick_actions.csv.description',
                'icon' => 'csv',
                'route' => 'app_csv_index',
                'color' => 'accent',
            ];
        }

        // Thème - pour Designers et Admins
        if ($this->isGranted('ROLE_DESIGNEUR') || $this->isGranted('ROLE_ADMIN')) {
            $actions[] = [
                'title' => 'dashboard.quick_actions.theme.title',
                'description' => 'dashboard.quick_actions.theme.description',
                'icon' => 'theme',
                'route' => 'app_theme_index',
                'color' => 'info',
            ];
        }

        // Utilisateurs - pour Admins uniquement
        if ($this->isGranted('ROLE_ADMIN')) {
            $actions[] = [
                'title' => 'dashboard.quick_actions.users.title',
                'description' => 'dashboard.quick_actions.users.description',
                'icon' => 'users',
                'route' => 'app_user_index',
                'color' => 'warning',
            ];
        }

        return $actions;
    }
}
