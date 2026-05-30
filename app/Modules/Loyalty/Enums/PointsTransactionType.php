<?php

declare(strict_types=1);

namespace Modules\Loyalty\Enums;

enum PointsTransactionType: string
{
    case EARNED = 'earned';
    case REDEEMED = 'redeemed';
    case EXPIRED = 'expired';
    case ADJUSTED = 'adjusted';
}
