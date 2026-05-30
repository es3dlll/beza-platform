<?php

declare(strict_types=1);

namespace Modules\Financing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Financing\Models\LoanProduct;

final class LoanProductFactory extends Factory
{
    protected $model = LoanProduct::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->word,
            'slug' => $this->faker->unique()->slug(1),
            'min_amount' => 100000,
            'max_amount' => 10000000,
            'currency' => 'SYP',
            'interest_rate' => $this->faker->randomFloat(2, 5, 25),
            'interest_type' => $this->faker->randomElement(['flat', 'declining', 'fixed']),
            'min_term_days' => 30,
            'max_term_days' => 365,
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
