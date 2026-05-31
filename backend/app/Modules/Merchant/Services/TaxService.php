<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Modules\Merchant\ValueObjects\TaxRule;

final class TaxService
{
    public function calculateTax(string $category, int $amount): array
    {
        $rule = TaxRule::forCategory($category);
        $taxAmount = $rule->calculateTax($amount);

        return [
            'category' => $category,
            'rate_bps' => $rule->rateBps(),
            'tax_amount' => $taxAmount,
            'is_exempt' => $rule->isExempt(),
        ];
    }
}
