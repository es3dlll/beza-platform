<?php

declare(strict_types=1);

namespace App\Modules\Agent\Enums;

final class AgentTransactionType
{
    const CASH_IN = 'CASH_IN';
    const CASH_OUT = 'CASH_OUT';
    const FLOAT_TRANSFER = 'FLOAT_TRANSFER';
    const COMMISSION_PAYOUT = 'COMMISSION_PAYOUT';
    const SETTLEMENT_WITHDRAWAL = 'SETTLEMENT_WITHDRAWAL';

    const ALL = [self::CASH_IN, self::CASH_OUT, self::FLOAT_TRANSFER, self::COMMISSION_PAYOUT, self::SETTLEMENT_WITHDRAWAL];

    const CASH_OPS = [self::CASH_IN, self::CASH_OUT];
}
