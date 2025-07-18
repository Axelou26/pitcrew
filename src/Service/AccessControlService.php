<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccessControlService
{
    /**
     * Messages d'erreur d'accès prédéfinis
     */
    private const ERROR_MESSAGES = [
        'not_authenticated' => 'Vous devez être connecté pour accéder à cette page.',
        'not_authorized' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
        'not_authorized_modify_offer' => 'Vous n\'êtes pas autorisé à modifier cette offre.',
        'not_authorized_delete_offer' => 'Vous n\'êtes pas autorisé à supprimer cette offre.',
        'not_authorized_schedule_interview' => 'Vous n\'êtes pas autorisé à planifier des entretiens pour cette offre.',
        'not_authorized_view_interview' => 'Vous n\'êtes pas autorisé à voir cet entretien.',
        'not_authorized_access_room' => 'Vous n\'êtes pas autorisé à accéder à cette salle.',
        'not_authorized_cancel_interview' => 'Vous n\'êtes pas autorisé à annuler cet entretien.',
        'not_authorized_end_interview' => 'Seul le recruteur peut terminer l\'entretien.',
        'not_authorized_view_interviews' => 'Vous n\'êtes pas autorisé à voir les entretiens pour cette offre.',
        'not_authorized_view_page' => 'Vous n\'êtes pas autorisé à voir cette page.',
        'not_authorized_access_notification' => 'Vous n\'êtes pas autorisé à accéder à cette notification.',
        'not_authorized_access_ticket' => 'Vous n\'êtes pas autorisé à accéder à ce ticket.',
        'not_authorized_add_recruiter_favorite' => 'Vous ne pouvez pas ajouter un recruteur en favoris',
        'invalid_access_link' => 'Lien d\'accès invalide.',
        'not_authorized_view_application' => 'Vous n\'êtes pas autorisé à voir cette candidature.',
        'not_authorized_modify_application' => 'Vous n\'êtes pas autorisé à modifier cette candidature.',
        'not_authorized_delete_application' => 'Vous n\'êtes pas autorisé à supprimer cette candidature.',
        'not_authorized_view_applications' => 'Vous n\'êtes pas autorisé à voir ces candidatures.',
    ];

    /**
     * Lance une exception d'accès refusé avec un message prédéfini
     */
    public function denyAccess(string $messageKey = 'not_authorized'): void
    {
        $message = self::ERROR_MESSAGES[$messageKey] ?? self::ERROR_MESSAGES['not_authorized'];
        throw new AccessDeniedException($message);
    }

    /**
     * Lance une exception d'accès refusé avec un message personnalisé
     */
    public function denyAccessWithMessage(string $message): void
    {
        throw new AccessDeniedException($message);
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public function requireAuthentication(): void
    {
        // Cette méthode peut être étendue pour des vérifications plus complexes
        // Pour l'instant, elle lance simplement une exception d'accès refusé
        $this->denyAccess('not_authenticated');
    }

    /**
     * Vérifie si l'utilisateur a le rôle requis
     */
    public function requireRole(string $role): void
    {
        // Cette méthode peut être étendue pour vérifier les rôles
        // Pour l'instant, elle lance simplement une exception d'accès refusé
        $this->denyAccess('not_authorized');
    }

    /**
     * Messages d'erreur spécifiques aux entités
     */
    public function denyAccessToEntity(string $entityName, string $action = 'accéder'): void
    {
        $message = "Vous n'êtes pas autorisé à {$action} à ce/ette {$entityName}.";
        $this->denyAccessWithMessage($message);
    }

    /**
     * Messages d'erreur pour les opérations CRUD
     */
    public function denyCreate(string $entityName): void
    {
        $this->denyAccessToEntity($entityName, 'créer');
    }

    public function denyRead(string $entityName): void
    {
        $this->denyAccessToEntity($entityName, 'voir');
    }

    public function denyUpdate(string $entityName): void
    {
        $this->denyAccessToEntity($entityName, 'modifier');
    }

    public function denyDelete(string $entityName): void
    {
        $this->denyAccessToEntity($entityName, 'supprimer');
    }
}
