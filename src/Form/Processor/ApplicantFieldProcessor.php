<?php

declare(strict_types=1);

namespace App\Form\Processor;

use App\Entity\Applicant;
use App\Entity\User;
use Symfony\Component\Form\FormInterface;

class ApplicantFieldProcessor implements UserFieldProcessorInterface
{
    public function processFields(User $user, FormInterface $form): void
    {
        if (!$user instanceof Applicant) {
            return;
        }

        $this->processCommonFields($user, $form);
        $this->processApplicantSpecificFields($user, $form);
    }

    public function supports(string $userType): bool
    {
        return $userType === User::ROLE_POSTULANT;
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

    private function processApplicantSpecificFields(Applicant $user, FormInterface $form): void
    {
        $fields = [
            'location'        => 'setLocation',
            'description'     => 'setDescription',
            'experienceLevel' => 'setExperienceLevel',
            'availability'    => 'setAvailability',
        ];

        foreach ($fields as $field => $setter) {
            if ($form->has($field)) {
                $user->$setter($form->get($field)->getData());
            }
        }

        $this->processTechnicalSkills($user, $form);
    }

    private function processTechnicalSkills(Applicant $user, FormInterface $form): void
    {
        if (!$form->has('technicalSkills')) {
            return;
        }

        $skills = $form->get('technicalSkills')->getData();
        if ($skills) {
            // Convertir la chaîne en tableau en séparant par des virgules
            $skillsArray = array_map('trim', explode(',', $skills));
            $user->setTechnicalSkills($skillsArray);
            return;
        }

        $user->setTechnicalSkills([]);
    }
}
