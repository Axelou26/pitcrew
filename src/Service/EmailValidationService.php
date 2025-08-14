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
            $this->validateDns($email),
        ];

        return array_values(array_filter($validations));
    }

    public function validateEmail(mixed $value, ExecutionContextInterface $context): void
    {
        if (!\is_string($value)) {
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

    private function isValidLength(string $email): bool
    {
        return \strlen($email) <= 254;
    }

    private function hasValidFormat(string $email): bool
    {
        if (str_contains($email, '..')) {
            return false;
        }

        if (str_contains($email, '.@') || str_contains($email, '@.')) {
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
        return str_contains($email, '..') ? 'L\'adresse email ne peut pas contenir deux points consécutifs' : null;
    }

    private function validateDotAroundAt(string $email): ?string
    {
        $hasInvalidDot = str_contains($email, '.@') || str_contains($email, '@.');
        $message       = 'L\'adresse email ne peut pas avoir un point juste avant ou après le @';

        return $hasInvalidDot ? $message : null;
    }

    private function validateEndingDot(string $email): ?string
    {
        $message = 'L\'adresse email ne peut pas se terminer par un point';

        return substr($email, -1) === '.' ? $message : null;
    }

    private function validateStartingDot(string $email): ?string
    {
        $message = 'L\'adresse email ne peut pas commencer par un point';

        return substr($email, 0, 1) === '.' ? $message : null;
    }

    private function validateSpaces(string $email): ?string
    {
        $message = 'L\'adresse email ne peut pas contenir d\'espaces';

        return str_contains($email, ' ') ? $message : null;
    }

    private function validateRegex(string $email): ?string
    {
        $message = 'Le format de l\'adresse email n\'est pas valide';

        return !preg_match(self::EMAIL_REGEX, $email) ? $message : null;
    }

    private function validateDns(string $email): ?string
    {
        $message = 'Le domaine de l\'adresse email n\'existe pas';

        return $this->checkDns && !$this->hasValidDomain($email) ? $message : null;
    }
}
