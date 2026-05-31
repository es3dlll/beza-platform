<?php

declare(strict_types=1);

namespace App\Modules\Core\ValueObjects;

use App\Modules\Core\Enums\Currency;
use App\Modules\Core\Exceptions\CurrencyMismatchException;
use App\Modules\Core\Exceptions\InvalidAmountException;

final readonly class Money
{
    public function __construct(
        private int $fils,
        private Currency $currency = Currency::SYP,
    ) {
        if ($fils < 0) {
            throw new InvalidAmountException('المبلغ لا يمكن أن يكون سالباً');
        }
    }

    public static function fromFils(int $fils, ?Currency $currency = null): self
    {
        return new self($fils, $currency ?? Currency::SYP);
    }

    public static function fromSYP(int|float $amount, ?Currency $currency = null): self
    {
        $fils = (int) round((float) $amount * 1000);

        return new self($fils, $currency ?? Currency::SYP);
    }

    public function fils(): int
    {
        return $this->fils;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->fils + $other->fils, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->fils - $other->fils, $this->currency);
    }

    public function multiply(int|float $multiplier): self
    {
        $fils = (int) round($this->fils * $multiplier);

        return new self($fils, $this->currency);
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->fils > $other->fils;
    }

    public function isLessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->fils < $other->fils;
    }

    public function equals(Money $other): bool
    {
        return $this->fils === $other->fils && $this->currency === $other->currency;
    }

    public function toSYP(): float
    {
        return $this->fils / 1000;
    }

    public function format(): string
    {
        $value = number_format($this->toSYP(), 3, '.', ',');

        return match ($this->currency) {
            Currency::SYP => "{$value} ل.س",
            Currency::USD => "{$value} \$",
            Currency::EUR => "{$value} €",
            Currency::TRY => "{$value} ₺",
        };
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException($this->currency, $other->currency);
        }
    }
}
