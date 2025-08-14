<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\Entity\Subscription;
use App\Entity\User;

interface PaymentServiceInterface
{
    public function createCustomer(User $user): string;

    public function createCheckoutSession(User $user, Subscription $subscription): object;

    /**
     * Gère le succès d'un paiement.
     *
     * @param array<string, mixed> $payload
     */
    public function handlePaymentSucceeded(array $payload): void;
}
