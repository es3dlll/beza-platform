<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Enums;

final class ComplianceTier
{
    const LOW = 'LOW';
    const MEDIUM = 'MEDIUM';
    const HIGH = 'HIGH';
    const SANCTIONED = 'SANCTIONED';

    private const MANUAL_REVIEW_THRESHOLDS = [
        self::LOW => PHP_INT_MAX,
        self::MEDIUM => 5_000_000,    // 5M SYP
        self::HIGH => 1_000_000,      // 1M SYP
        self::SANCTIONED => 0,
    ];

    public static function requiresManualReview(string $tier, int $amount): bool
    {
        $threshold = self::MANUAL_REVIEW_THRESHOLDS[$tier] ?? self::MANUAL_REVIEW_THRESHOLDS[self::MEDIUM];
        return $amount >= $threshold;
    }

    public static function isBlocked(string $tier): bool
    {
        return $tier === self::SANCTIONED;
    }

    public static function all(): array
    {
        return [self::LOW, self::MEDIUM, self::HIGH, self::SANCTIONED];
    }
}
