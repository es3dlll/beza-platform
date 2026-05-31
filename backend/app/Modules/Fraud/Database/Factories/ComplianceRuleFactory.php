<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Database\Factories;

use App\Modules\Fraud\Models\ComplianceRule;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ComplianceRuleFactory extends Factory
{
    protected $model = ComplianceRule::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'key' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'rule_type' => fake()->randomElement(['amount', 'frequency', 'region', 'device']),
            'parameters' => ['threshold' => fake()->numberBetween(1000, 100000)],
            'is_active' => true,
            'priority' => fake()->numberBetween(0, 50),
            'risk_score_impact' => fake()->numberBetween(5, 50),
            'decision' => fake()->randomElement(['approved', 'suspended', 'rejected']),
        ];
    }
}
