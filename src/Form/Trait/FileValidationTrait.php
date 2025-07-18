<?php

declare(strict_types=1);

namespace App\Form\Trait;

use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;

trait FileValidationTrait
{
    /**
     * Constantes pour les types MIME
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
     * Constantes pour les tailles de fichiers
     */
    private const MAX_SIZE_SMALL = '2M';
    private const MAX_SIZE_MEDIUM = '5M';
    private const MAX_SIZE_LARGE = '10M';

    /**
     * Crée une contrainte File pour les PDF
     */
    protected function createPdfFileConstraint(string $maxSize = self::MAX_SIZE_MEDIUM): File
    {
        return new File([
            'maxSize' => $maxSize,
            'mimeTypes' => self::MIME_TYPES_PDF,
            'maxSizeMessage' => 'Le fichier est trop volumineux. ' .
                'La taille maximale autorisée est {{ limit }}',
            'mimeTypesMessage' => 'Seuls les fichiers PDF sont acceptés',
            'notFoundMessage' => 'Le fichier n\'a pas été trouvé',
            'notReadableMessage' => 'Le fichier n\'est pas lisible',
            'uploadErrorMessage' => 'Erreur lors du téléchargement du fichier',
        ]);
    }

    /**
     * Crée une contrainte File pour les images
     */
    protected function createImageFileConstraint(
        string $maxSize = self::MAX_SIZE_MEDIUM,
        bool $includeSvg = false
    ): File {
        $mimeTypes = $includeSvg ? self::MIME_TYPES_IMAGES_WITH_SVG : self::MIME_TYPES_IMAGES;

        return new File([
            'maxSize' => $maxSize,
            'mimeTypes' => $mimeTypes,
            'maxSizeMessage' => 'L\'image est trop volumineuse. ' .
                'La taille maximale autorisée est {{ limit }}',
            'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF' .
                ($includeSvg ? ', SVG' : '') . ')',
            'notFoundMessage' => 'L\'image n\'a pas été trouvée',
            'notReadableMessage' => 'L\'image n\'est pas lisible',
            'uploadErrorMessage' => 'Erreur lors du téléchargement de l\'image',
        ]);
    }

    /**
     * Crée une contrainte All pour les fichiers multiples
     */
    protected function createMultipleFilesConstraint(
        string $maxSize = self::MAX_SIZE_MEDIUM,
        array $mimeTypes = null,
        int $maxCount = 5
    ): All {
        $mimeTypes = $mimeTypes ?? self::MIME_TYPES_PDF;

        return new All([
            'constraints' => [
                new File([
                    'maxSize' => $maxSize,
                    'mimeTypes' => $mimeTypes,
                    'mimeTypesMessage' => 'Veuillez télécharger des fichiers valides',
                ])
            ]
        ]);
    }

    /**
     * Crée une contrainte Count pour limiter le nombre de fichiers
     */
    protected function createFileCountConstraint(int $maxCount = 5): Count
    {
        return new Count([
            'max' => $maxCount,
            'maxMessage' => 'Vous ne pouvez pas télécharger plus de {{ limit }} fichiers',
        ]);
    }

    /**
     * Obtient les attributs HTML pour un champ de fichier PDF
     */
    protected function getPdfFileAttributes(string $class = 'form-control'): array
    {
        return [
            'accept' => 'application/pdf',
            'class' => $class,
            'data-bs-toggle' => 'tooltip',
            'title' => 'Format accepté : PDF, taille maximale : 5 Mo'
        ];
    }

    /**
     * Obtient les attributs HTML pour un champ de fichier image
     */
    protected function getImageFileAttributes(string $class = 'form-control', bool $includeSvg = false): array
    {
        $accept = $includeSvg
            ? 'image/jpeg,image/png,image/gif,image/svg+xml'
            : 'image/jpeg,image/png,image/gif';

        return [
            'accept' => $accept,
            'class' => $class,
        ];
    }

    /**
     * Obtient les attributs HTML pour un champ de fichiers multiples
     */
    protected function getMultipleFilesAttributes(int $maxCount = 5): array
    {
        return [
            'accept' => 'application/pdf',
            'data-max-files' => $maxCount
        ];
    }
}
