<?php

declare(strict_types=1);

namespace App\Modules\Remittance\ValueObjects;

use App\Modules\Remittance\Enums\CurrencyCode;

final readonly class ExchangeRate
{
    private const DEFAULT_TTL = 60;
    private const MAX_VOLATILITY_BPS = 100;

    public function __construct(
        private string $fromCurrency,
        private string $toCurrency,
        private int $buyRate,      // المنصة تشتري
        private int $sellRate,     // المنصة تبيع
        private int $spreadBps,
        private int $lockedAt,
    ) {
        CurrencyCode::assertValid($fromCurrency);
        CurrencyCode::assertValid($toCurrency);
    }

    public static function fresh(string $from, string $to, int $buy, int $sell, int $spreadBps): self
    {
        return new self($from, $to, $buy, $sell, $spreadBps, time());
    }

    public function isExpired(): bool
    {
        return (time() - $this->lockedAt) > self::DEFAULT_TTL;
    }

    public function remainingSeconds(): int
    {
        return max(0, self::DEFAULT_TTL - (time() - $this->lockedAt));
    }

    public function isVolatileComparedTo(self $other): bool
    {
        $diffBps = abs($this->buyRate - $other->buyRate) * 10000 / max($other->buyRate, 1);
        return $diffBps > self::MAX_VOLATILITY_BPS;
    }

    public function buyRate(): int
    {
        return $this->buyRate;
    }

    public function sellRate(): int
    {
        return $this->sellRate;
    }

    public function fromCurrency(): string
    {
        return $this->fromCurrency;
    }

    public function toCurrency(): string
    {
        return $this->toCurrency;
    }

    public function spreadBps(): int
    {
        return $this->spreadBps;
    }

    public function toArray(): array
    {
        return [
            'from' => $this->fromCurrency,
            'to' => $this->toCurrency,
            'buy_rate' => $this->buyRate,
            'sell_rate' => $this->sellRate,
            'spread_bps' => $this->spreadBps,
            'expires_in' => $this->remainingSeconds(),
        ];
    }
}
