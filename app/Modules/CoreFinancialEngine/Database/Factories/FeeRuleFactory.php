<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\CoreFinancialEngine\Models\FeeRule;

class FeeRuleFactory extends Factory
{
    protected $model = FeeRule::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->word,
            'fee_type' => $this->faker->randomElement(['flat', 'percentage', 'tiered']),
            'fee_calculation' => $this->faker->randomElement(['fixed', 'percentage', 'tiered']),
            'fee_amount' => $this->faker->numberBetween(100, 50000),
            'fee_percentage' => null,
            'fee_cap' => null,
            'fee_floor' => null,
            'fee_account_number' => (string) Str::ulid(),
            'min_amount' => null,
            'max_amount' => null,
            'currency' => 'SYP',
            'priority' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
            'conditions' => null,
        ];
    }

    public function percentage(float $pct = 2.5): static
    {
        return $this->state(fn() => [
            'fee_type' => 'percentage',
            'fee_calculation' => 'percentage',
            'fee_amount' => null,
            'fee_percentage' => $pct,
        ]);
    }
}
