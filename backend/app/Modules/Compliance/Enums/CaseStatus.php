<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Enums;

final class CaseStatus
{
    const OPEN = 'OPEN';
    const UNDER_REVIEW = 'UNDER_REVIEW';
    const ESCALATED = 'ESCALATED';
    const RESOLVED_FALSE_POSITIVE = 'RESOLVED_FALSE_POSITIVE';
    const RESOLVED_TRUE_POSITIVE = 'RESOLVED_TRUE_POSITIVE';
    const CLOSED = 'CLOSED';

    private const TRANSITIONS = [
        self::OPEN => [self::UNDER_REVIEW, self::ESCALATED, self::CLOSED],
        self::UNDER_REVIEW => [self::RESOLVED_FALSE_POSITIVE, self::RESOLVED_TRUE_POSITIVE, self::ESCALATED],
        self::ESCALATED => [self::UNDER_REVIEW, self::RESOLVED_TRUE_POSITIVE, self::CLOSED],
        self::RESOLVED_FALSE_POSITIVE => [self::CLOSED],
        self::RESOLVED_TRUE_POSITIVE => [self::CLOSED],
        self::CLOSED => [],
    ];

    public static function assertTransition(string $from, string $to): void
    {
        if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new \RuntimeException("Invalid case status transition: {$from} → {$to}");
        }
    }
}
