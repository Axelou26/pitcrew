<?php

declare(strict_types=1);

namespace App\Form\Trait;

use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;

trait FileValidationTrait
{
    /**
     * Constantes pour les types MIME.
     */
    private const MIME_TYPES_PDF = [
        'application/pdf',
        'application/x-pdf',
    ];

    private const MIME_TYPES_IMAGES = [
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

    private const MIME_TYPES_IMAGES_WITH_SVG = [
        'image/jpeg',
        'image/png',
        'image/svg+xml',
    ];

    /**
     * Constantes pour les tailles de fichiers.
     */
    private const MAX_SIZE_SMALL  = '2M';
    private const MAX_SIZE_MEDIUM = '5M';
    private const MAX_SIZE_LARGE  = '10M';

    /**
     * Crée une contrainte File pour les PDF.
     */
    protected function createPdfFileConstraint(string $maxSize = self::MAX_SIZE_MEDIUM): File
    {
        return new File([
            'maxSize'        => $maxSize,
            'mimeTypes'      => self::MIME_TYPES_PDF,
            'maxSizeMessage' => 'Le fichier est trop volumineux. ' .
                'La taille maximale autorisée est {{ limit }}',
            'mimeTypesMessage'   => 'Seuls les fichiers PDF sont acceptés',
            'notFoundMessage'    => 'Le fichier n\'a pas été trouvé',
            'notReadableMessage' => 'Le fichier n\'est pas lisible',
            'uploadErrorMessage' => 'Erreur lors du téléchargement du fichier',
        ]);
    }

    /**
     * Crée une contrainte File pour les images.
     */
    protected function createImageFileConstraint(
        string $maxSize = self::MAX_SIZE_MEDIUM,
        bool $includeSvg = false
    ): File {
        $mimeTypes = $includeSvg ? self::MIME_TYPES_IMAGES_WITH_SVG : self::MIME_TYPES_IMAGES;

        return new File([
            'maxSize'        => $maxSize,
            'mimeTypes'      => $mimeTypes,
            'maxSizeMessage' => 'L\'image est trop volumineuse. ' .
                'La taille maximale autorisée est {{ limit }}',
            'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF' .
                ($includeSvg ? ', SVG' : '') . ')',
            'notFoundMessage'    => 'L\'image n\'a pas été trouvée',
            'notReadableMessage' => 'L\'image n\'est pas lisible',
            'uploadErrorMessage' => 'Erreur lors du téléchargement de l\'image',
        ]);
    }

    /**
     * Crée une contrainte pour les fichiers multiples.
     *
     * @param array<int, string> $mimeTypes
     */
    protected function createMultipleFilesConstraint(array $mimeTypes): All
    {
        return new All([
            new File([
                'maxSize'          => $this->getMaxFileSize(),
                'mimeTypes'        => $mimeTypes,
                'mimeTypesMessage' => 'Veuillez télécharger un fichier valide.',
            ]),
        ]);
    }

    /**
     * Crée une contrainte Count pour limiter le nombre de fichiers.
     */
    protected function createFileCountConstraint(int $maxCount = 5): Count
    {
        return new Count([
            'max'        => $maxCount,
            'maxMessage' => 'Vous ne pouvez pas télécharger plus de {{ limit }} fichiers',
        ]);
    }

    /**
     * Récupère les attributs pour les fichiers PDF.
     *
     * @return array<string, mixed>
     */
    protected function getPdfFileAttributes(): array
    {
        return [
            'class'         => 'form-control',
            'accept'        => '.pdf',
            'data-max-size' => $this->getMaxFileSize(),
        ];
    }

    /**
     * Récupère les attributs pour les fichiers image.
     *
     * @return array<string, mixed>
     */
    protected function getImageFileAttributes(): array
    {
        return [
            'class'         => 'form-control',
            'accept'        => 'image/*',
            'data-max-size' => $this->getMaxFileSize(),
        ];
    }

    /**
     * Récupère les attributs pour les fichiers multiples.
     *
     * @return array<string, mixed>
     */
    protected function getMultipleFilesAttributes(): array
    {
        return [
            'class'         => 'form-control',
            'multiple'      => true,
            'data-max-size' => $this->getMaxFileSize(),
        ];
    }
}
