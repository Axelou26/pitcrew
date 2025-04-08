<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EmailValidationService
{
    private const EMAIL_REGEX =
        '/^[a-zA-Z0-9.!#$%&\'*+\\/=?^_`{|}~-]+' .
        '@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?' .
        '(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/';

    private bool $checkDns;

    public function __construct(string $env = 'prod')
    {
        $this->checkDns = ($env !== 'test');
    }

    public function isValidEmail(string $email): bool
    {
        if (strlen($email) > 254) {
            return false;
        }

        if (strpos($email, ' . . ') !== false) {
            return false;
        }

        if (strpos($email, ' . @') !== false || strpos($email, '@ . ') !== false) {
            return false;
        }

        if (substr($email, -1) === ' . ') {
            return false;
        }

        if (!preg_match(self::EMAIL_REGEX, $email)) {
            return false;
        }

        // Vérification du domaine uniquement en production
        if ($this->checkDns) {
            $atPos = strpos($email, '@');
            if ($atPos === false) {
                return false;
            }
            $domain = substr($email, $atPos + 1);
            if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    public function getValidationErrors(string $email): array
    {
        $errors = [];

        if (strlen($email) > 254) {
            $errors[] = 'L\'adresse email ne doit pas dépasser 254 caractères';
        }

        if (strpos($email, '..') !== false) {
            $errors[] = 'L\'adresse email ne peut pas contenir deux points consécutifs';
        }

        if (strpos($email, '.@') !== false || strpos($email, '@.') !== false) {
            $errors[] = 'L\'adresse email ne peut pas avoir un point juste avant ou après le @';
        }

        if (substr($email, -1) === '.') {
            $errors[] = 'L\'adresse email ne peut pas se terminer par un point';
        }

        if (!preg_match(self::EMAIL_REGEX, $email)) {
            $errors[] = 'Le format de l\'adresse email n\'est pas valide';
        }

        if ($this->checkDns) {
            $atPos = strpos($email, '@');
            if ($atPos !== false) {
                $domain = substr($email, $atPos + 1);
                if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
                    $errors[] = 'Le domaine de l\'adresse email n\'existe pas';
                }
            }
        }

        return $errors;
    }

    public function validateEmail(mixed $value, ExecutionContextInterface $context): void
    {
        if (!is_string($value)) {
            $context->buildViolation('L\'email doit être une chaîne de caractères')
                ->addViolation();
            return;
        }

        $errors = $this->getValidationErrors($value);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $context->buildViolation($error)
                    ->addViolation();
            }
        }
    }
}
