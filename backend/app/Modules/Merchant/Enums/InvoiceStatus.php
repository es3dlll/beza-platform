<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Enums;

final class InvoiceStatus
{
    const DRAFT = 'DRAFT';
    const PENDING_PAYMENT = 'PENDING_PAYMENT';
    const PAID = 'PAID';
    const EXPIRED = 'EXPIRED';
    const CANCELLED = 'CANCELLED';
    const REFUNDED = 'REFUNDED';

    private const ALLOWED = [
        self::DRAFT, self::PENDING_PAYMENT, self::PAID,
        self::EXPIRED, self::CANCELLED, self::REFUNDED,
    ];

    private const TRANSITIONS = [
        self::DRAFT => [self::PENDING_PAYMENT, self::CANCELLED],
        self::PENDING_PAYMENT => [self::PAID, self::EXPIRED, self::CANCELLED],
        self::PAID => [self::REFUNDED],
        self::EXPIRED => [],
        self::CANCELLED => [],
        self::REFUNDED => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function assertTransition(string $from, string $to): void
    {
        if (!self::canTransition($from, $to)) {
            throw new \RuntimeException("Invalid invoice status transition: {$from} → {$to}");
        }
    }
}
