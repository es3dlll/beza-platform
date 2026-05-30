<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class Rate
{
    private function __construct(
        private readonly float $value,
        private readonly Currency $from,
        private readonly Currency $to
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('Rate must be positive');
        }
    }

    public static function fromFloat(float $value, Currency $from, Currency $to): self
    {
        return new self($value, $from, $to);
    }

    public function convert(Money $amount): Money
    {
        if (!$amount->getCurrency()->equals($this->from)) {
            throw new InvalidArgumentException('Currency mismatch for this rate');
        }
        return Money::fromInt(
            (int) round($amount->toInt() * $this->value),
            $this->to
        );
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getFrom(): Currency
    {
        return $this->from;
    }

    public function getTo(): Currency
    {
        return $this->to;
    }
}
