<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class FlashMessageService
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Ajoute un message de succès.
     */
    public function addSuccess(string $message): void
    {
        $this->requestStack->getSession()->getFlashBag()->add('success', $message);
    }

    /**
     * Ajoute un message d'erreur.
     */
    public function addError(string $message): void
    {
        $this->requestStack->getSession()->getFlashBag()->add('error', $message);
    }

    /**
     * Ajoute un message d'avertissement.
     */
    public function addWarning(string $message): void
    {
        $this->requestStack->getSession()->getFlashBag()->add('warning', $message);
    }

    /**
     * Ajoute un message d'information.
     */
    public function addInfo(string $message): void
    {
        $this->requestStack->getSession()->getFlashBag()->add('info', $message);
    }

    /**
     * Messages prédéfinis pour les entités.
     */
    public function entityCreated(string $entityName): void
    {
        $this->addSuccess("Le/La {$entityName} a été créé(e) avec succès.");
    }

    public function entityUpdated(string $entityName): void
    {
        $this->addSuccess("Le/La {$entityName} a été mis(e) à jour avec succès.");
    }

    public function entityDeleted(string $entityName): void
    {
        $this->addSuccess("Le/La {$entityName} a été supprimé(e) avec succès.");
    }

    public function entityNotFound(string $entityName): void
    {
        $this->addError("Le/La {$entityName} n'existe pas.");
    }

    public function accessDenied(): void
    {
        $this->addError("Vous n'êtes pas autorisé à effectuer cette action.");
    }

    public function validationError(string $message): void
    {
        $this->addError($message);
    }

    public function operationSuccess(string $operation): void
    {
        $this->addSuccess($operation);
    }

    public function operationFailed(string $operation, string $reason = ''): void
    {
        $message = "L'opération '{$operation}' a échoué";
        if ($reason) {
            $message .= " : {$reason}";
        }
        $this->addError($message);
    }
}
