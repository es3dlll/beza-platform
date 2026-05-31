<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Enums;

final class SettlementCycle
{
    const DAILY = 'DAILY';
    const WEEKLY = 'WEEKLY';
    const INSTANT = 'INSTANT';

    const COMMISSION_BPS = [
        self::DAILY => 100,
        self::WEEKLY => 80,
        self::INSTANT => 150,
    ];

    const SETTLEMENT_HOURS = [
        self::DAILY => 24,
        self::WEEKLY => 168,
        self::INSTANT => 0,
    ];

    public static function commissionBps(string $cycle): int
    {
        return self::COMMISSION_BPS[$cycle] ?? self::COMMISSION_BPS[self::DAILY];
    }

    public static function settlementWindow(string $cycle): int
    {
        return self::SETTLEMENT_HOURS[$cycle] ?? self::SETTLEMENT_HOURS[self::DAILY];
    }

    public static function isValid(string $cycle): bool
    {
        return in_array($cycle, [self::DAILY, self::WEEKLY, self::INSTANT], true);
    }
}
