<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserRegistrationValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function validateRegistration(User $user, FormInterface $form): ?array
    {
        $errors = [];

        if ($this->isEmailAlreadyUsed($user)) {
            $errors[] = 'Cette adresse email est déjà utilisée.';
        }

        $validationErrors = $this->validator->validate($user);
        if (count($validationErrors) > 0) {
            foreach ($validationErrors as $error) {
                $errors[] = $error->getMessage();
            }
        }

        return empty($errors) ? null : $errors;
    }

    private function isEmailAlreadyUsed(User $user): bool
    {
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $user->getEmail()]);

        return $existingUser !== null;
    }
}
