<?php

namespace App\Core\ValueObjects;

class Currency
{
    private function __construct(
        private readonly string $code,
        private readonly string $nameAr,
        private readonly string $nameEn,
        private readonly string $symbol,
        private readonly int $decimals = 4,
    ) {}

    public static function SYP(): self
    {
        return new self('SYP', 'ليرة سورية', 'Syrian Pound', '£S', 4);
    }

    public static function USD(): self
    {
        return new self('USD', 'دولار أمريكي', 'US Dollar', '$', 4);
    }

    public static function fromCode(string $code): self
    {
        return match (strtoupper($code)) {
            'SYP' => self::SYP(),
            'USD' => self::USD(),
            default => throw new \InvalidArgumentException("غير مدعومة: $code"),
        };
    }

    public static function all(): array
    {
        return [self::SYP(), self::USD()];
    }

    public function code(): string { return $this->code; }
    public function nameAr(): string { return $this->nameAr; }
    public function nameEn(): string { return $this->nameEn; }
    public function symbol(): string { return $this->symbol; }
    public function decimals(): int { return $this->decimals; }
}
