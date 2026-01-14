<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/chat', name: 'api_chat_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ChatApiController extends AbstractController
{
    private const ALLOWED_ROLES = [
        'ROLE_AUTEUR',
        'ROLE_EDITEUR',
        'ROLE_FOURNISSEUR',
        'ROLE_DESIGNEUR',
        'ROLE_ADMIN',
    ];

    private const QUICK_COMMANDS = [
        '/cms' => "🏠 **CMS Backoffice:** Plateforme de gestion de contenu pour créer et publier des articles, gérer les médias (images), importer des datasets CSV pour visualisations, et organiser le contenu par thèmes. Accessible selon votre rôle utilisateur.",
        '/articles' => "📝 **Créer un article:** Articles > Nouveau > titre + contenu CKEditor > Enregistrer",
        '/roles' => "👥 **Rôles:** AUTEUR (ses articles), EDITEUR (tous), FOURNISSEUR (CSV), DESIGNEUR (thèmes), ADMIN (tout)",
        '/medias' => "🖼️ **Upload média:** Médias > Upload ou glisser-déposer. Formats: JPG/PNG/GIF/WebP, max 5MB",
        '/csv' => "📊 **Importer CSV:** Datasets CSV > Importer. Max 10MB. Graphiques disponibles: barres, lignes, camemberts",
        '/themes' => "🎨 **Thèmes:** Menu Thèmes pour catégoriser le contenu. Mode sombre: bouton dans la sidebar",
        '/profil' => "👤 **Profil:** Menu profil en haut à droite. Avatar: JPG/PNG/WebP max 2MB",
        '/aide' => "💡 **Commandes disponibles:** /cms, /articles, /roles, /medias, /csv, /themes, /profil",
        '/help' => "💡 **Available commands:** /cms, /articles, /roles, /medias, /csv, /themes, /profil",
    ];

    public function __construct(
        private readonly HttpClientInterface $localaiClient,
        private readonly CacheInterface $cache
    ) {}

    #[Route('', name: 'message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->hasAllowedRole($user->getRoles())) {
            return new JsonResponse(
                ['error' => 'Access denied. Insufficient permissions.'],
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['message']) || trim($data['message']) === '') {
                return new JsonResponse(
                    ['error' => 'Message cannot be empty'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $userMessage = trim($data['message']);

            if (strlen($userMessage) > 1000) {
                return new JsonResponse(
                    ['error' => 'Message too long. Maximum 1000 characters.'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Quick commands - instant response, no LLM call
            $quickResponse = $this->handleQuickCommand($userMessage);
            if ($quickResponse !== null) {
                return new JsonResponse([
                    'message' => $quickResponse,
                    'timestamp' => time(),
                ]);
            }

            // Check cache for common queries
            $cacheKey = 'chat_' . md5(strtolower($userMessage));
            $botMessage = $this->cache->get($cacheKey, function (ItemInterface $item) use ($userMessage) {
                $item->expiresAfter(3600); // Cache for 1 hour
                return $this->callLLM($userMessage);
            });

            return new JsonResponse([
                'message' => $botMessage,
                'timestamp' => time(),
            ]);
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            return new JsonResponse(
                ['error' => 'Unable to connect to AI service. Please try again later.'],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'An error occurred while processing your request'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function handleQuickCommand(string $message): ?string
    {
        $lowerMessage = strtolower(trim($message));
        return self::QUICK_COMMANDS[$lowerMessage] ?? null;
    }

    private function callLLM(string $userMessage): string
    {
        $contextPath = $this->getParameter('kernel.project_dir') . '/help/cms_context.txt';
        $systemContext = file_exists($contextPath)
            ? file_get_contents($contextPath)
            : 'Tu es un assistant CMS concis. Réponses: 2-3 phrases max.';

        $payload = [
            'model' => 'llama3.2:1b',
            'messages' => [
                ['role' => 'system', 'content' => $systemContext],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'stream' => false,
            'options' => [
                'num_predict' => 150,    // Max tokens for response
                'temperature' => 0.3,    // More deterministic/concise
            ],
        ];

        try {
            $response = $this->localaiClient->request('POST', '/api/chat', [
                'json' => $payload,
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('LocalAI returned non-200 status');
            }

            $responseData = $response->toArray();

            return $responseData['message']['content']
                ?? $responseData['choices'][0]['message']['content']
                ?? 'No response available';
        } catch (\Exception $e) {
            return $this->getDemoResponse($userMessage);
        }
    }

    private function hasAllowedRole(array $userRoles): bool
    {
        return !empty(array_intersect($userRoles, self::ALLOWED_ROLES));
    }

    private function getDemoResponse(string $userMessage): string
    {
        $lowerMessage = strtolower($userMessage);

        $responses = [
            'article' => "📝 Articles > Nouveau > titre + contenu > Enregistrer",
            'user' => "👥 Rôles: AUTEUR, EDITEUR, FOURNISSEUR, DESIGNEUR, ADMIN",
            'image' => "🖼️ Médias > Upload. Formats: JPG/PNG/GIF/WebP, max 5MB",
            'csv' => "📊 Datasets CSV > Importer. Max 10MB, graphiques auto",
            'theme' => "🎨 Menu Thèmes. Mode sombre: bouton sidebar",
        ];

        foreach ($responses as $keyword => $response) {
            if (str_contains($lowerMessage, $keyword)) {
                return "[Mode démo]\n" . $response;
            }
        }

        return "[Mode démo]\nAssistant CMS. Tapez /aide pour les commandes disponibles.";
    }
}
