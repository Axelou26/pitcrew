<?php

declare(strict_types=1);

namespace App\Form\Processor;

use App\Entity\Recruiter;
use App\Entity\User;
use Symfony\Component\Form\FormInterface;

class RecruiterFieldProcessor implements UserFieldProcessorInterface
{
    public function processFields(User $user, FormInterface $form): void
    {
        if (!$user instanceof Recruiter) {
            return;
        }

        $this->processCommonFields($user, $form);
        $this->processRecruiterSpecificFields($user, $form);
    }

    public function supports(string $userType): bool
    {
        return $userType === User::ROLE_RECRUTEUR;
    }

    private function processCommonFields(User $user, FormInterface $form): void
    {
        if ($form->has('phone')) {
            $user->setPhone($form->get('phone')->getData());
        }
        if ($form->has('city')) {
            $user->setCity($form->get('city')->getData());
        }
    }

    private function processRecruiterSpecificFields(Recruiter $user, FormInterface $form): void
    {
        $fields = ['sector', 'companySize', 'website', 'companyDescription'];

        foreach ($fields as $field) {
            if ($form->has($field)) {
                $setter = 'set' . ucfirst($field);
                $user->$setter($form->get($field)->getData());
            }
        }
    }
}
