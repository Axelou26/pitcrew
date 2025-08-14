<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Email extends Constraint
{
    public string $message = 'L\'adresse email "{{ value }}" n\'est pas valide.';

    public function validatedBy(): string
    {
        return EmailValidator::class;
    }
}
