<?php

namespace App\Service;

use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ImageOptimizationService
{
    private const MAX_WIDTH = 1920;
    private const THUMB_SIZE = 400;
    private const WEBP_QUALITY = 75;
    private const THUMB_QUALITY = 70;

    public function __construct(
        private readonly ImageManager $imageManager,
        private readonly SluggerInterface $slugger,
        private readonly string $projectDir
    ) {}

    public function processUpload(UploadedFile $file): array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $uniqueId = uniqid();
        $baseName = $safeFilename . '-' . $uniqueId;

        $outputDir = $this->projectDir . '/public/media/images';

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $image = $this->imageManager->read($file->getPathname());

        $optimizedFilename = $baseName . '.webp';
        $thumbFilename = $baseName . '-thumb.webp';

        $image->scaleDown(width: self::MAX_WIDTH)
              ->toWebp(quality: self::WEBP_QUALITY)
              ->save($outputDir . '/' . $optimizedFilename);

        $image->cover(width: self::THUMB_SIZE, height: self::THUMB_SIZE)
              ->toWebp(quality: self::THUMB_QUALITY)
              ->save($outputDir . '/' . $thumbFilename);

        $originalSize = $file->getSize();
        $optimizedSize = filesize($outputDir . '/' . $optimizedFilename);
        $savings = $originalSize > 0 ? round((1 - $optimizedSize / $originalSize) * 100) : 0;

        return [
            'filename' => $optimizedFilename,
            'thumbnail' => $thumbFilename,
            'url' => '/media/images/' . $optimizedFilename,
            'thumbnailUrl' => '/media/images/' . $thumbFilename,
            'originalSize' => $originalSize,
            'optimizedSize' => $optimizedSize,
            'savings' => $savings,
        ];
    }

    public function optimizeExistingImage(string $inputPath, ?string $outputPath = null): string
    {
        $outputPath = $outputPath ?? $inputPath;

        $this->imageManager->read($inputPath)
            ->scaleDown(width: self::MAX_WIDTH)
            ->toWebp(quality: self::WEBP_QUALITY)
            ->save($outputPath);

        return $outputPath;
    }

    public function generateThumbnail(string $inputPath, string $outputPath): string
    {
        $this->imageManager->read($inputPath)
            ->cover(width: self::THUMB_SIZE, height: self::THUMB_SIZE)
            ->toWebp(quality: self::THUMB_QUALITY)
            ->save($outputPath);

        return $outputPath;
    }
}
