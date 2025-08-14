<?php

declare(strict_types=1);

namespace App\Service;

interface PaymentServiceInterface
{
    /**
     * Gère le succès d'un paiement.
     *
     * @param array<string, mixed> $data
     */
    public function handlePaymentSucceeded(array $data): void;

    /**
     * Gère l'échec d'un paiement.
     *
     * @param array<string, mixed> $data
     */
    public function handlePaymentFailed(array $data): void;

    /**
     * Gère l'annulation d'un abonnement.
     *
     * @param array<string, mixed> $data
     */
    public function handleSubscriptionCancelled(array $data): void;
}
