<?php

declare(strict_types=1);

namespace Modules\Financing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Financing\Models\Loan;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'product_id' => (string) Str::ulid(),
            'amount' => $this->faker->numberBetween(100000, 5000000),
            'currency' => 'SYP',
            'interest_rate' => $this->faker->randomFloat(2, 5, 25),
            'term_days' => $this->faker->randomElement([30, 60, 90, 180, 365]),
            'total_amount' => $this->faker->numberBetween(110000, 6000000),
            'paid_amount' => 0,
            'status' => 'pending',
            'application_date' => now(),
            'approved_date' => null,
            'disbursed_date' => null,
            'metadata' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn() => [
            'status' => 'approved',
            'approved_date' => now(),
        ]);
    }
}
