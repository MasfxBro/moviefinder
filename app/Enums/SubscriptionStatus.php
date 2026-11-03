<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Expired = 'expired';
}
