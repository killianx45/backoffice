<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

    public function __construct(
        private readonly HttpClientInterface $localaiClient
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

            $contextPath = $this->getParameter('kernel.project_dir') . '/help/cms_context.txt';
            $systemContext = file_exists($contextPath)
                ? file_get_contents($contextPath)
                : 'You are a helpful assistant for a CMS backoffice. Answer questions about managing articles, users, media, and datasets.';

            $payload = [
                'model' => 'llama3.2:1b',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemContext
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage
                    ]
                ],
                'stream' => false,
            ];

            try {
                $response = $this->localaiClient->request('POST', '/api/chat', [
                    'json' => $payload,
                    'timeout' => 60,
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    throw new \Exception('LocalAI returned non-200 status');
                }

                $responseData = $response->toArray();

                // Ollama format: response.message.content
                // OpenAI format: response.choices[0].message.content
                $botMessage = $responseData['message']['content']
                    ?? $responseData['choices'][0]['message']['content']
                    ?? 'No response available';
            } catch (\Exception $e) {
                $botMessage = $this->getDemoResponse($userMessage);
            }

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

    private function hasAllowedRole(array $userRoles): bool
    {
        return !empty(array_intersect($userRoles, self::ALLOWED_ROLES));
    }

    private function getDemoResponse(string $userMessage): string
    {
        $lowerMessage = strtolower($userMessage);

        $responses = [
            'article' => "Pour créer un article : 1) Allez dans 'Articles' > 'Nouveau', 2) Remplissez le titre et le contenu avec l'éditeur CKEditor, 3) Ajoutez des images via le gestionnaire de médias, 4) Cliquez sur 'Enregistrer'. Les articles peuvent avoir les statuts : Brouillon, Publié ou Archivé.",
            'user' => "Les rôles disponibles sont : ROLE_AUTEUR (créer ses articles), ROLE_EDITEUR (modifier tous les articles), ROLE_FOURNISSEUR (uploader des CSV), ROLE_DESIGNEUR (gérer les thèmes), ROLE_ADMIN (accès complet).",
            'image' => "Pour uploader des images : allez dans 'Médias', cliquez sur 'Upload' ou glissez-déposez. Formats acceptés : JPG, PNG, GIF, WebP (max 5MB).",
            'csv' => "Pour importer un CSV : menu 'Datasets CSV' > 'Importer', sélectionnez votre fichier (max 10MB). Le système détecte automatiquement les colonnes pour créer des graphiques.",
            'theme' => "Les thèmes permettent de catégoriser le contenu. Menu 'Thèmes' pour créer/gérer. Le mode sombre/clair se bascule via le bouton en haut de la sidebar.",
        ];

        foreach ($responses as $keyword => $response) {
            if (str_contains($lowerMessage, $keyword)) {
                return "🤖 [Mode démo - LocalAI en configuration]\n\n" . $response;
            }
        }

        return "🤖 [Mode démo - LocalAI en configuration]\n\nJe suis votre assistant CMS. Je peux vous aider avec : la création d'articles, la gestion des utilisateurs et rôles, l'upload d'images et de datasets CSV, et la personnalisation des thèmes. Que souhaitez-vous savoir ?";
    }
}
