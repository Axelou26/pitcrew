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
        if (!$this->isValidLength($email) || !$this->hasValidFormat($email)) {
            return false;
        }

        return !$this->checkDns || $this->hasValidDomain($email);
    }

    private function isValidLength(string $email): bool
    {
        return strlen($email) <= 254;
    }

    private function hasValidFormat(string $email): bool
    {
        if (strpos($email, '..') !== false) {
            return false;
        }

        if (strpos($email, '.@') !== false || strpos($email, '@.') !== false) {
            return false;
        }

        if (substr($email, -1) === '.') {
            return false;
        }

        return preg_match(self::EMAIL_REGEX, $email) === 1;
    }

    private function hasValidDomain(string $email): bool
    {
        $atPos = strpos($email, '@');
        if ($atPos === false) {
            return false;
        }

        $domain = substr($email, $atPos + 1);
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }

    /**
     * @return array<int, string>
     */
    public function getValidationErrors(string $email): array
    {
        $errors = [];

        if (!$this->isValidLength($email)) {
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

        if ($this->checkDns && !$this->hasValidDomain($email)) {
            $errors[] = 'Le domaine de l\'adresse email n\'existe pas';
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
