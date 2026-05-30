<?php

namespace App\Domain\ValueObjects;

use App\Domain\ValueObjects\Currency;
use InvalidArgumentException;

final class Money
{
    private function __construct(
        private readonly int $amount,
        private readonly Currency $currency
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }

    public static function fromInt(int $amount, Currency $currency): self
    {
        return new self($amount, $currency);
    }

    public static function fromFloat(float $amount, Currency $currency): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new InvalidArgumentException('Cannot add different currencies');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new InvalidArgumentException('Cannot subtract different currencies');
        }
        if ($other->amount > $this->amount) {
            throw new InvalidArgumentException('Insufficient funds');
        }
        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiplyBy(int|float $factor): self
    {
        return new self((int) round($this->amount * $factor), $this->currency);
    }

    public function greaterThan(self $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->amount >= $other->amount;
    }

    public function lessThan(self $other): bool
    {
        return $this->amount < $other->amount;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency->equals($other->currency);
    }

    public function toInt(): int
    {
        return $this->amount;
    }

    public function toFloat(): float
    {
        return $this->amount / 100;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }
}
