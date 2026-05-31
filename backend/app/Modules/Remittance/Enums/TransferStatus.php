<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Enums;

final class TransferStatus
{
    const PENDING = 'PENDING';
    const FX_LOCKED = 'FX_LOCKED';
    const COMPLIANCE_CHECK = 'COMPLIANCE_CHECK';
    const PROCESSING = 'PROCESSING';
    const SETTLED = 'SETTLED';
    const REJECTED = 'REJECTED';
    const CANCELLED = 'CANCELLED';
    const EXPIRED = 'EXPIRED';

    private const ALLOWED = [
        self::PENDING, self::FX_LOCKED, self::COMPLIANCE_CHECK,
        self::PROCESSING, self::SETTLED, self::REJECTED,
        self::CANCELLED, self::EXPIRED,
    ];

    private const TRANSITIONS = [
        self::PENDING => [self::FX_LOCKED, self::CANCELLED, self::REJECTED],
        self::FX_LOCKED => [self::COMPLIANCE_CHECK, self::REJECTED, self::EXPIRED],
        self::COMPLIANCE_CHECK => [self::PROCESSING, self::REJECTED, self::CANCELLED],
        self::PROCESSING => [self::SETTLED, self::REJECTED, self::EXPIRED],
        self::SETTLED => [],
        self::REJECTED => [],
        self::CANCELLED => [],
        self::EXPIRED => [],
    ];

    public static function allowed(): array
    {
        return self::ALLOWED;
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function assertTransition(string $from, string $to): void
    {
        if (!self::canTransition($from, $to)) {
            throw new \RuntimeException("Invalid status transition: {$from} → {$to}");
        }
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::ALLOWED, true);
    }
}
