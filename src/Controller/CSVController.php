<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/csv')]
#[IsGranted('ROLE_FOURNISSEUR')]
class CSVController extends AbstractController
{
    private const MAX_FILE_SIZE = 2 * 1024 * 1024;
    private const ALLOWED_MIME_TYPES = ['text/csv', 'text/plain', 'application/csv'];

    #[Route('/cms/csv', name: 'app_csv_index')]
    public function index(): Response
    {
        return $this->render('csv/index.html.twig', [
            'controller_name' => 'CSVController',
        ]);
    }

    #[Route('/files', name: 'app_csv_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $csvDirectory = $this->getParameter('kernel.project_dir') . '/public/media/csv';

        if (!is_dir($csvDirectory)) {
            return $this->json(['csvFiles' => []]);
        }

        $files = array_diff(scandir($csvDirectory), ['.', '..', '.gitignore']);
        $csvFiles = [];

        foreach ($files as $file) {
            $filePath = $csvDirectory . '/' . $file;
            if (is_file($filePath) && pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                $csvFiles[] = [
                    'filename' => $file,
                    'url' => '/media/csv/' . $file,
                    'size' => filesize($filePath),
                    'uploadedAt' => filemtime($filePath),
                ];
            }
        }

        usort($csvFiles, fn($a, $b) => $b['uploadedAt'] <=> $a['uploadedAt']);

        return $this->json(['csvFiles' => $csvFiles]);
    }

    #[Route('/upload', name: 'app_csv_upload', methods: ['POST'])]
    public function upload(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'Aucun fichier fourni'], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return $this->json(['error' => 'Le fichier est trop volumineux (max 2MB)'], Response::HTTP_BAD_REQUEST);
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            return $this->json(['error' => 'Type de fichier non autorisé. Seuls les fichiers CSV sont acceptés.'], Response::HTTP_BAD_REQUEST);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.csv';

        try {
            $csvDirectory = $this->getParameter('kernel.project_dir') . '/public/media/csv';

            if (!is_dir($csvDirectory)) {
                mkdir($csvDirectory, 0755, true);
            }

            $file->move($csvDirectory, $newFilename);

            return $this->json([
                'success' => true,
                'filename' => $newFilename,
                'url' => '/media/csv/' . $newFilename,
                'message' => 'Fichier CSV uploadé avec succès'
            ]);
        } catch (FileException $e) {
            return $this->json(['error' => 'Erreur lors de l\'upload du fichier'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{filename}', name: 'app_csv_delete', methods: ['DELETE'])]
    public function delete(string $filename): JsonResponse
    {
        $csvDirectory = $this->getParameter('kernel.project_dir') . '/public/media/csv';
        $filePath = $csvDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            return $this->json(['error' => 'Fichier introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (!str_ends_with($filename, '.csv')) {
            return $this->json(['error' => 'Type de fichier invalide'], Response::HTTP_BAD_REQUEST);
        }

        try {
            unlink($filePath);
            return $this->json(['success' => true, 'message' => 'Fichier supprimé avec succès']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la suppression du fichier'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/preview/{filename}', name: 'app_csv_preview', methods: ['GET'])]
    public function preview(string $filename): JsonResponse
    {
        $csvDirectory = $this->getParameter('kernel.project_dir') . '/public/media/csv';
        $filePath = $csvDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            return $this->json(['error' => 'Fichier introuvable'], Response::HTTP_NOT_FOUND);
        }

        try {
            $content = file_get_contents($filePath);
            $lines = array_slice(explode("\n", $content), 0, 10);

            return $this->json([
                'success' => true,
                'filename' => $filename,
                'preview' => implode("\n", $lines),
                'lineCount' => count(explode("\n", $content))
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la lecture du fichier'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
