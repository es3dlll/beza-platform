<?php

declare(strict_types=1);

namespace Modules\Loyalty\Enums;

enum CashbackTrigger: string
{
    case TRANSACTION_AMOUNT = 'transaction_amount';
    case MERCHANT_CATEGORY = 'merchant_category';
    case FREQUENCY = 'frequency';
    case TIER_BONUS = 'tier_bonus';
}
