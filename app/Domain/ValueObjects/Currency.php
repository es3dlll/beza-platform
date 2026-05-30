<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class Currency
{
    private const SUPPORTED = ['SYP', 'USD'];

    private function __construct(
        private readonly string $code,
        private readonly int $minorUnit
    ) {}

    public static function SYP(): self
    {
        return new self('SYP', 2);
    }

    public static function USD(): self
    {
        return new self('USD', 2);
    }

    public static function fromCode(string $code): self
    {
        return match (strtoupper($code)) {
            'SYP' => self::SYP(),
            'USD' => self::USD(),
            default => throw new InvalidArgumentException("Unsupported currency: $code"),
        };
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMinorUnit(): int
    {
        return $this->minorUnit;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
