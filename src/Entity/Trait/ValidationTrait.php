<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use Symfony\Component\Validator\Constraints as Assert;
use DateTime;

trait ValidationTrait
{
    /**
     * Contraintes de validation communes pour les champs obligatoires
     */
    public static function getNotBlankConstraints(string $message = null): array
    {
        return [
            new Assert\NotBlank([
                'message' => $message ?? 'Ce champ est obligatoire'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les emails
     */
    public static function getEmailConstraints(): array
    {
        return [
            new Assert\NotBlank([
                'message' => 'L\'adresse email est obligatoire'
            ]),
            new Assert\Email([
                'message' => 'L\'adresse email "{{ value }}" n\'est pas valide'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les mots de passe
     */
    public static function getPasswordConstraints(): array
    {
        return [
            new Assert\NotBlank([
                'message' => 'Le mot de passe est obligatoire'
            ]),
            new Assert\Length([
                'min' => 8,
                'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                'max' => 4096,
                'maxMessage' => 'Le mot de passe ne peut pas dépasser {{ limit }} caractères'
            ]),
            new Assert\Regex([
                'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'message' => 'Le mot de passe doit contenir au moins une minuscule, une majuscule, ' .
                    'un chiffre et un caractère spécial'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les noms
     */
    public static function getNameConstraints(): array
    {
        return [
            new Assert\NotBlank([
                'message' => 'Le nom est obligatoire'
            ]),
            new Assert\Length([
                'min' => 2,
                'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                'max' => 50,
                'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
            ]),
            new Assert\Regex([
                'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/',
                'message' => 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les prénoms
     */
    public static function getFirstNameConstraints(): array
    {
        return [
            new Assert\NotBlank([
                'message' => 'Le prénom est obligatoire'
            ]),
            new Assert\Length([
                'min' => 2,
                'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                'max' => 50,
                'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
            ]),
            new Assert\Regex([
                'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/',
                'message' => 'Le prénom ne peut contenir que des lettres, espaces, tirets et apostrophes'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les titres
     */
    public static function getTitleConstraints(int $min = 3, int $max = 100): array
    {
        return [
            new Assert\NotBlank([
                'message' => 'Le titre est obligatoire'
            ]),
            new Assert\Length([
                'min' => $min,
                'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                'max' => $max,
                'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les descriptions
     */
    public static function getDescriptionConstraints(int $min = 10, int $max = 1000): array
    {
        return [
            new Assert\NotBlank([
                'message' => 'La description est obligatoire'
            ]),
            new Assert\Length([
                'min' => $min,
                'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                'max' => $max,
                'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les commentaires
     */
    public static function getCommentConstraints(): array
    {
        return [
            new Assert\NotBlank([
                'message' => 'Le commentaire ne peut pas être vide'
            ]),
            new Assert\Length([
                'min' => 1,
                'minMessage' => 'Le commentaire ne peut pas être vide',
                'max' => 500,
                'maxMessage' => 'Le commentaire ne peut pas dépasser {{ limit }} caractères'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les messages
     */
    public static function getMessageConstraints(): array
    {
        return [
            new Assert\NotBlank([
                'message' => 'Le message ne peut pas être vide'
            ]),
            new Assert\Length([
                'min' => 1,
                'minMessage' => 'Le message ne peut pas être vide',
                'max' => 1000,
                'maxMessage' => 'Le message ne peut pas dépasser {{ limit }} caractères'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les URLs
     */
    public static function getUrlConstraints(): array
    {
        return [
            new Assert\Url([
                'message' => 'L\'URL "{{ value }}" n\'est pas valide'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les numéros de téléphone
     */
    public static function getPhoneConstraints(): array
    {
        return [
            new Assert\Regex([
                'pattern' => '/^(\+33|0)[1-9](\d{8})$/',
                'message' => 'Le numéro de téléphone doit être un numéro français valide'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les codes postaux
     */
    public static function getPostalCodeConstraints(): array
    {
        return [
            new Assert\Regex([
                'pattern' => '/^[0-9]{5}$/',
                'message' => 'Le code postal doit contenir exactement 5 chiffres'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les montants
     */
    public static function getAmountConstraints(): array
    {
        return [
            new Assert\Positive([
                'message' => 'Le montant doit être positif'
            ]),
            new Assert\Range([
                'min' => 0,
                'max' => 999999.99,
                'notInRangeMessage' => 'Le montant doit être compris entre {{ min }} et {{ max }}'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les pourcentages
     */
    public static function getPercentageConstraints(): array
    {
        return [
            new Assert\Range([
                'min' => 0,
                'max' => 100,
                'notInRangeMessage' => 'Le pourcentage doit être compris entre {{ min }} et {{ max }}'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les dates
     */
    public static function getDateConstraints(): array
    {
        return [
            new Assert\NotBlank([
                'message' => 'La date est obligatoire'
            ]),
            new Assert\Type([
                'type' => 'DateTimeInterface',
                'message' => 'La valeur doit être une date valide'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les dates futures
     */
    public static function getFutureDateConstraints(): array
    {
        return [
            new Assert\NotBlank([
                'message' => 'La date est obligatoire'
            ]),
            new Assert\GreaterThan([
                'value' => new DateTime(),
                'message' => 'La date doit être dans le futur'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les entiers positifs
     */
    public static function getPositiveIntegerConstraints(): array
    {
        return [
            new Assert\Positive([
                'message' => 'La valeur doit être positive'
            ]),
            new Assert\Type([
                'type' => 'integer',
                'message' => 'La valeur doit être un nombre entier'
            ])
        ];
    }

    /**
     * Contraintes de validation pour les entiers dans une plage
     */
    public static function getIntegerRangeConstraints(int $min, int $max): array
    {
        return [
            new Assert\Type([
                'type' => 'integer',
                'message' => 'La valeur doit être un nombre entier'
            ]),
            new Assert\Range([
                'min' => $min,
                'max' => $max,
                'notInRangeMessage' => 'La valeur doit être comprise entre {{ min }} et {{ max }}'
            ])
        ];
    }
}
