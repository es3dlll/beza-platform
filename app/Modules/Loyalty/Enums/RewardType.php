<?php

declare(strict_types=1);

namespace Modules\Loyalty\Enums;

enum RewardType: string
{
    case CASHBACK = 'cashback';
    case DISCOUNT = 'discount';
    case FEE_WAIVER = 'fee_waiver';
    case GIFT = 'gift';
}
