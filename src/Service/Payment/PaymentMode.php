<?php

declare(strict_types=1);

namespace App\Service\Payment;

enum PaymentMode
{
    case LIVE;
    case TEST;
    case OFFLINE;
}
