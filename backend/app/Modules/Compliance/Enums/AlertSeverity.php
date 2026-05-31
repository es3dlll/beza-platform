<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Enums;

final class AlertSeverity
{
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const HIGH = 'HIGH';
    const CRITICAL = 'CRITICAL';

    const ESCALATION = [
        self::INFO => false,
        self::WARNING => false,
        self::HIGH => true,
        self::CRITICAL => true,
    ];

    public static function requiresEscalation(string $severity): bool
    {
        return self::ESCALATION[$severity] ?? false;
    }
}
