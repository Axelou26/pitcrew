<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Subscription;

interface PaymentServiceInterface
{
    public function createCustomer(User $user): string;
    public function createCheckoutSession(User $user, Subscription $subscription, string $customerId): object;
    public function handlePaymentSucceeded(array $data): void;
    public function handlePaymentFailed(array $data): void;
    public function handleSubscriptionCancelled(array $data): void;
} 