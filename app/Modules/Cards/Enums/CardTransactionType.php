<?php

declare(strict_types=1);

namespace Modules\Cards\Enums;

enum CardTransactionType: string
{
    case PURCHASE = 'purchase';
    case ATM_WITHDRAWAL = 'atm_withdrawal';
    case REFUND = 'refund';
    case FEE = 'fee';
    case AUTHORIZATION_HOLD = 'authorization_hold';
}
