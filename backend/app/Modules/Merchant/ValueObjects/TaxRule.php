<?php

declare(strict_types=1);

namespace App\Modules\Merchant\ValueObjects;

final readonly class TaxRule
{
    public function __construct(
        private string $category,
        private int $rateBps,
        private bool $isExempt,
    ) {}

    private const TAX_RULES = [
        'goods_food' => ['rate_bps' => 0, 'exempt' => true],
        'goods_general' => ['rate_bps' => 500, 'exempt' => false],
        'goods_luxury' => ['rate_bps' => 1000, 'exempt' => false],
        'services_general' => ['rate_bps' => 800, 'exempt' => false],
        'services_digital' => ['rate_bps' => 1000, 'exempt' => false],
        'services_financial' => ['rate_bps' => 500, 'exempt' => false],
    ];

    public static function forCategory(string $category): self
    {
        $rule = self::TAX_RULES[$category] ?? self::TAX_RULES['goods_general'];
        return new self($category, $rule['rate_bps'], $rule['exempt']);
    }

    public function calculateTax(int $amount): int
    {
        return $this->isExempt ? 0 : intdiv($amount * $this->rateBps, 10000);
    }

    public function category(): string { return $this->category; }
    public function rateBps(): int { return $this->rateBps; }
    public function isExempt(): bool { return $this->isExempt; }
}
