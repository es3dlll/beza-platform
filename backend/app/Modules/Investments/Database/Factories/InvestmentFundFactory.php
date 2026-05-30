<?php

declare(strict_types=1);

namespace Modules\Investments\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Investments\Models\InvestmentFund;
use Illuminate\Support\Str;

class InvestmentFundFactory extends Factory
{
    protected $model = InvestmentFund::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'name' => $this->faker->company() . ' Fund',
            'name_ar' => 'صندوق ' . $this->faker->company(),
            'type' => $this->faker->randomElement(['equity', 'sukuk', 'real_estate', 'commodity']),
            'description' => $this->faker->sentence(),
            'min_investment' => 100000,
            'max_investment' => null,
            'current_nav' => 100000,
            'nav_updated_at' => now(),
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
