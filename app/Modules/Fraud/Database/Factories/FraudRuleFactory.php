<?php

declare(strict_types=1);

namespace Modules\Fraud\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Fraud\Models\FraudRule;

class FraudRuleFactory extends Factory
{
    protected $model = FraudRule::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->word,
            'rule_type' => $this->faker->randomElement(['velocity', 'geolocation', 'device', 'amount', 'pattern']),
            'configuration' => json_encode(['max_attempts' => 5, 'window_minutes' => 60]),
            'risk_level' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'is_active' => true,
            'priority' => $this->faker->numberBetween(1, 100),
        ];
    }
}
