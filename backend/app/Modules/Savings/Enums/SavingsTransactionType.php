<?php

declare(strict_types=1);

namespace Modules\Savings\Enums;

enum SavingsTransactionType: string
{
    case CONTRIBUTION = 'contribution';
    case WITHDRAWAL = 'withdrawal';
    case PROFIT = 'profit';
    case PENALTY = 'penalty';
    case AUTO_SWEEP = 'auto_sweep';
}
