<?php

declare(strict_types=1);

namespace Modules\Investments\Enums;

enum SubscriptionStatus: string
{
    case PENDING = 'pending';
    case SETTLED = 'settled';
    case FAILED = 'failed';
    case REDEEMED = 'redeemed';
}
