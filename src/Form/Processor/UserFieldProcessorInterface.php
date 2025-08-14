<?php

declare(strict_types=1);

namespace App\Form\Processor;

use App\Entity\User;
use Symfony\Component\Form\FormInterface;

interface UserFieldProcessorInterface
{
    public function processFields(User $user, FormInterface $form): void;
    public function supports(string $userType): bool;
}
