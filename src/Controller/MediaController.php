<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_AUTEUR')]
class MediaController extends AbstractController
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {}

    #[Route('/cms/media', name: 'app_media_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('media/index.html.twig');
    }

    #[Route('/api/media/upload/image', name: 'api_media_upload_image', methods: ['POST'])]
    public function uploadImage(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            return $this->json(['error' => $this->translator->trans('media.error.no_file')], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return $this->json(['error' => $this->translator->trans('media.error.file_too_large')], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_IMAGE_TYPES)) {
            return $this->json(['error' => $this->translator->trans('media.error.invalid_type')], Response::HTTP_BAD_REQUEST);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move(
                $this->getParameter('kernel.project_dir') . '/public/media/images',
                $newFilename
            );

            return $this->json([
                'success' => true,
                'url' => '/media/images/' . $newFilename,
                'filename' => $newFilename,
            ]);
        } catch (FileException $e) {
            return $this->json([
                'error' => $this->translator->trans('media.error.upload_failed', ['%message%' => $e->getMessage()])
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/media/images', name: 'api_media_list_images', methods: ['GET'])]
    public function listImages(): JsonResponse
    {
        $imagesPath = $this->getParameter('kernel.project_dir') . '/public/media/images';

        if (!is_dir($imagesPath)) {
            return $this->json(['images' => []]);
        }

        $files = array_diff(scandir($imagesPath), ['.', '..']);
        $images = [];

        foreach ($files as $file) {
            $filePath = $imagesPath . '/' . $file;
            if (is_file($filePath)) {
                $images[] = [
                    'filename' => $file,
                    'url' => '/media/images/' . $file,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath),
                ];
            }
        }

        usort($images, fn($a, $b) => $b['modified'] <=> $a['modified']);

        return $this->json(['images' => $images]);
    }

    #[Route('/api/media/delete/image/{filename}', name: 'api_media_delete_image', methods: ['DELETE'])]
    public function deleteImage(string $filename): JsonResponse
    {
        // Security: Validate filename to prevent directory traversal
        if (
            !preg_match('/^[a-zA-Z0-9._-]+$/', $filename) ||
            strpos($filename, '..') !== false ||
            strpos($filename, '/') !== false ||
            strpos($filename, '\\') !== false
        ) {
            return $this->json(['error' => 'Invalid filename'], Response::HTTP_BAD_REQUEST);
        }

        // Use basename for additional security
        $filename = basename($filename);
        $filePath = $this->getParameter('kernel.project_dir') . '/public/media/images/' . $filename;

        if (!file_exists($filePath)) {
            return $this->json(['error' => $this->translator->trans('media.error.not_found')], Response::HTTP_NOT_FOUND);
        }

        if (unlink($filePath)) {
            return $this->json(['success' => true, 'message' => $this->translator->trans('media.success.deleted')]);
        }

        return $this->json(['error' => $this->translator->trans('media.error.delete_failed')], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
