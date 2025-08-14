<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Recruiter;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use ReflectionClass;
use ReflectionException;

class RecruiterLifecycleSubscriber implements EventSubscriberInterface
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

        // Vérifier si l'entité est un Recruiter
        if (!$entity instanceof Recruiter) {
            return;
        }

        // S'assurer que toutes les collections sont initialisées
        $this->ensureCollectionsInitialized($entity);
    }

    private function ensureCollectionsInitialized(Recruiter $recruiter): void
    {
        // Utiliser la réflexion pour accéder aux propriétés privées et les initialiser si elles sont nulles
        $reflectionClass = new ReflectionClass(Recruiter::class);

        try {
            $property = $reflectionClass->getProperty('favoriteApplicants');
            $property->setAccessible(true);

            // Ne pas accéder directement à la propriété, utiliser une méthode alternative pour l'initialiser
            if (
                !isset($recruiter->_propertyInitialized)
                || !in_array('favoriteApplicants', $recruiter->_propertyInitialized, true)
            ) {
                $property->setValue($recruiter, new ArrayCollection());

                // Marquer la propriété comme initialisée
                if (!isset($recruiter->_propertyInitialized)) {
                    $recruiter->_propertyInitialized = [];
                }
                $recruiter->_propertyInitialized[] = 'favoriteApplicants';
            }
        } catch (ReflectionException $e) {
            // Ignorer l'erreur si la propriété n'existe pas
        }
    }
}
