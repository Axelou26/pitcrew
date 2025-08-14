<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Applicant;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use ReflectionClass;
use ReflectionException;

class ApplicantLifecycleSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::postLoad,
        ];
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // Vérifier si l'entité est un Applicant
        if (!$entity instanceof Applicant) {
            return;
        }

        // S'assurer que tous les champs array sont initialisés
        $this->ensureArrayFieldsInitialized($entity);
    }

    private function ensureArrayFieldsInitialized(Applicant $applicant): void
    {
        // Utiliser la réflexion pour accéder aux propriétés privées et les initialiser si elles sont nulles
        $reflectionClass = new ReflectionClass(Applicant::class);

        // Initialiser les champs de type array
        $arrayFields = [
            'technicalSkills',
            'softSkills',
            'educationHistory',
            'workExperience',
        ];

        foreach ($arrayFields as $field) {
            try {
                $property = $reflectionClass->getProperty($field);
                $property->setAccessible(true);

                // Si la propriété est null, l'initialiser avec un tableau vide
                if ($property->getValue($applicant) === null) {
                    $property->setValue($applicant, []);
                }
            } catch (ReflectionException $e) {
                // Ignorer l'erreur si la propriété n'existe pas
            }
        }

        // Initialiser les champs booléens
        try {
            $isActiveProperty = $reflectionClass->getProperty('isActive');
            $isActiveProperty->setAccessible(true);

            // Si la propriété est null, l'initialiser avec true par défaut
            if ($isActiveProperty->getValue($applicant) === null) {
                $isActiveProperty->setValue($applicant, true);
            }
        } catch (ReflectionException $e) {
            // Ignorer l'erreur si la propriété n'existe pas
        }
    }
}
