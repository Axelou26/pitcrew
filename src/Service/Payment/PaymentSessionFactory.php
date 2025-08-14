<?php

declare(strict_types=1);

namespace App\Service\Payment;

use stdClass;

class PaymentSessionFactory
{
    public function createOfflineSession(string $successUrl): stdClass
    {
        $session                     = new stdClass();
        $session->id                 = 'offline_' . uniqid();
        $session->url                = $successUrl;
        $session->is_offline_session = true;

        return $session;
    }
}
