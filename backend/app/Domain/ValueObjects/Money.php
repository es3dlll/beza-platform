<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Enums\Currency;
use InvalidArgumentException;

final readonly class Money
{
    public function __construct(
        private int $amount,
        private Currency $currency = Currency::SYP,
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative');
        }
    }

    public static function fromInt(int $amount, Currency $currency = Currency::SYP): self
    {
        return new self($amount, $currency);
    }

    public static function zero(Currency $currency = Currency::SYP): self
    {
        return new self(0, $currency);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        $result = $this->amount - $other->amount;
        if ($result < 0) {
            throw new InvalidArgumentException('Insufficient funds');
        }
        return new self($result, $this->currency);
    }

    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    public function greaterThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount >= $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function multiplyBy(int $multiplier): self
    {
        return new self($this->amount * $multiplier, $this->currency);
    }

    public function percentage(int $basisPoints): self
    {
        $result = (int) floor(($this->amount * $basisPoints) / 10000);
        return new self($result, $this->currency);
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency->value,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                sprintf('Currency mismatch: %s vs %s', $this->currency->value, $other->currency->value)
            );
        }
    }
}
