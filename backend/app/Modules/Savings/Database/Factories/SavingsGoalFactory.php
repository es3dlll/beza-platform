<?php

declare(strict_types=1);

namespace Modules\Savings\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Savings\Models\SavingsGoal;

final class SavingsGoalFactory extends Factory
{
    protected $model = SavingsGoal::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'name' => $this->faker->word,
            'target_amount' => $this->faker->numberBetween(100000, 10000000),
            'currency' => 'SYP',
            'current_amount' => 0,
            'status' => 'active',
            'target_date' => now()->addMonths(12),
            'metadata' => null,
        ];
    }
}
