<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum Currency: string
{
    case SYP = 'SYP';
    case USD = 'USD';

    public function minorUnitName(): string
    {
        return match ($this) {
            self::SYP => 'piaster',
            self::USD => 'cent',
        };
    }

    public function decimalPlaces(): int
    {
        return match ($this) {
            self::SYP => 2,
            self::USD => 2,
        };
    }

    public function isFiat(): bool
    {
        return true;
    }
}
