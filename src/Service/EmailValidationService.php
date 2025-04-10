<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EmailValidationService
{
    private const EMAIL_REGEX = '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9]'
        . '(?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?'
        . '(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/';

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

        if (substr($email, 0, 1) === '.') {
            return false;
        }

        if (substr_count($email, '@') !== 1) {
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

    private function validateLength(string $email): ?string
    {
        return !$this->isValidLength($email) ? 'L\'adresse email ne doit pas dépasser 254 caractères' : null;
    }

    private function validateAtSymbol(string $email): ?string
    {
        return substr_count($email, '@') !== 1 ? 'L\'adresse email doit contenir exactement un @' : null;
    }

    private function validateConsecutiveDots(string $email): ?string
    {
        return strpos($email, '..') !== false ? 'L\'adresse email ne peut pas contenir deux points consécutifs' : null;
    }

    private function validateDotAroundAt(string $email): ?string
    {
        $hasInvalidDot = strpos($email, '.@') !== false || strpos($email, '@.') !== false;
        $message = 'L\'adresse email ne peut pas avoir un point juste avant ou après le @';
        return $hasInvalidDot ? $message : null;
    }

    private function validateEndingDot(string $email): ?string
    {
        return substr($email, -1) === '.' ? 'L\'adresse email ne peut pas se terminer par un point' : null;
    }

    private function validateStartingDot(string $email): ?string
    {
        return substr($email, 0, 1) === '.' ? 'L\'adresse email ne peut pas commencer par un point' : null;
    }

    private function validateSpaces(string $email): ?string
    {
        return strpos($email, ' ') !== false ? 'L\'adresse email ne peut pas contenir d\'espaces' : null;
    }

    private function validateRegex(string $email): ?string
    {
        return !preg_match(self::EMAIL_REGEX, $email) ? 'Le format de l\'adresse email n\'est pas valide' : null;
    }

    private function validateDns(string $email): ?string
    {
        return $this->checkDns && !$this->hasValidDomain($email) ? 'Le domaine de l\'adresse email n\'existe pas' : null;
    }

    /**
     * @return array<int, string>
     */
    public function getValidationErrors(string $email): array
    {
        $validations = [
            $this->validateLength($email),
            $this->validateAtSymbol($email),
            $this->validateConsecutiveDots($email),
            $this->validateDotAroundAt($email),
            $this->validateEndingDot($email),
            $this->validateStartingDot($email),
            $this->validateSpaces($email),
            $this->validateRegex($email),
            $this->validateDns($email)
        ];

        return array_values(array_filter($validations));
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
