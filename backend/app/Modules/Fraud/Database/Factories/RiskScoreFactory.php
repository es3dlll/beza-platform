<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Database\Factories;

use App\Modules\Fraud\Models\RiskScore;
use Illuminate\Database\Eloquent\Factories\Factory;

final class RiskScoreFactory extends Factory
{
    protected $model = RiskScore::class;

    public function definition(): array
    {
        return [
            'score' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement(['approved', 'suspended', 'rejected']),
            'reasons' => [],
            'request_type' => 'liquidity',
            'request_id' => fake()->uuid(),
            'user_id' => (string) fake()->ulid(),
            'amount_fils' => fake()->numberBetween(100_000, 50_000_000),
            'currency' => 'SYP',
            'region' => fake()->randomElement(['damascus', 'aleppo', 'homs', 'latakia']),
        ];
    }
}
