<?php

declare(strict_types=1);

namespace App\Service\Post;

use App\Service\FileUploader;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PostImageHandler
{
    public function __construct(
        private readonly FileUploader $fileUploader,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handleImageUpload(?UploadedFile $imageFile, ?string $existingImage = null): ?string
    {
        if (!$imageFile) {
            return null;
        }

        try {
            return $this->fileUploader->upload(
                $imageFile,
                'posts_directory',
                $existingImage
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement de l\'image', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
