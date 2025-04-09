<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\Entity\Subscription;
use App\Entity\User;
use Stripe\Checkout\Session;
use stdClass;

interface PaymentServiceInterface
{
    public function createCheckoutSession(User $user, Subscription $subscription): Session|stdClass;
    public function handlePaymentSucceeded(array $payload): void;
    public function isTestMode(): bool;
    public function isOfflineMode(): bool;
}
