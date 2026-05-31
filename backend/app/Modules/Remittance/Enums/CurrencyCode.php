<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Enums;

final class CurrencyCode
{
    private const ALLOWED = ['SYP', 'USD', 'EUR', 'SAR', 'AED'];
    private const ISO_4217 = [
        'SYP' => '760',
        'USD' => '840',
        'EUR' => '978',
        'SAR' => '682',
        'AED' => '784',
    ];

    public static function allowed(): array
    {
        return self::ALLOWED;
    }

    public static function isValid(string $code): bool
    {
        return in_array(strtoupper($code), self::ALLOWED, true);
    }

    public static function isoCode(string $code): string
    {
        $upper = strtoupper($code);
        return self::ISO_4217[$upper] ?? throw new \InvalidArgumentException("Invalid currency code: {$code}");
    }

    public static function assertValid(string $code): void
    {
        if (!self::isValid($code)) {
            throw new \InvalidArgumentException("Currency '{$code}' is not allowed. Allowed: " . implode(', ', self::ALLOWED));
        }
    }
}
