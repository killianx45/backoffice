<?php

namespace App\EventListener;

use App\Entity\Article;
use Intervention\Image\ImageManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;

#[AsEventListener(event: Events::PRE_UPLOAD, method: 'onPreUpload')]
final class ImageUploadListener
{
    private const MAX_WIDTH = 1920;
    private const WEBP_QUALITY = 75;

    public function __construct(
        private readonly ImageManager $imageManager
    ) {}

    public function onPreUpload(Event $event): void
    {
        $object = $event->getObject();
        $mapping = $event->getMapping();

        if (!$object instanceof Article) {
            return;
        }

        if ($mapping->getMappingName() !== 'article_featured_images') {
            return;
        }

        $file = $object->getFeaturedImageFile();
        if (!$file instanceof File) {
            return;
        }

        $tempPath = $file->getPathname();
        $optimizedPath = sys_get_temp_dir() . '/' . uniqid('optimized_') . '.webp';

        $this->imageManager->read($tempPath)
            ->scaleDown(width: self::MAX_WIDTH)
            ->toWebp(quality: self::WEBP_QUALITY)
            ->save($optimizedPath);

        $optimizedFile = new File($optimizedPath);
        $object->setFeaturedImageFile($optimizedFile);
    }
}
