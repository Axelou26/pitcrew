<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Service\EmailValidationService;

class EmailValidator extends ConstraintValidator
{
    private EmailValidationService $emailValidationService;

    public function __construct(EmailValidationService $emailValidationService)
    {
        $this->emailValidationService = $emailValidationService;
    }

    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            $this->context->buildViolation('L\'email doit être une chaîne de caractères')
                ->addViolation();
            return;
        }

        $errors = $this->emailValidationService->getValidationErrors($value);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->context->buildViolation($error)
                    ->addViolation();
            }
        }
    }
}
