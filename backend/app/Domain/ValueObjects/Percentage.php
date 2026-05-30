<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class Percentage
{
    private function __construct(
        private readonly float $value
    ) {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100');
        }
    }

    public static function fromFloat(float $value): self
    {
        return new self($value);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function applyTo(Money $amount): Money
    {
        $result = (int) round($amount->toInt() * ($this->value / 100));
        return Money::fromInt($result, $amount->getCurrency());
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function asFraction(): float
    {
        return $this->value / 100;
    }
}
