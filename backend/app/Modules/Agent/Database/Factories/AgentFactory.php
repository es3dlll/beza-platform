<?php

declare(strict_types=1);

namespace Modules\Agent\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Agent\Models\Agent;

final class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'business_name' => $this->faker->company,
            'agent_type' => $this->faker->randomElement(['super', 'standard', 'light']),
            'governorate' => $this->faker->randomElement(['دمشق', 'حلب', 'حمص', 'حماه', 'اللاذقية']),
            'city' => $this->faker->city,
            'location' => null,
            'coverage_radius' => 5.0,
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
            'status' => 'pending',
            'liquidity_score' => null,
            'last_liquidity_check' => null,
            'metadata' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn() => [
            'is_approved' => true,
            'approved_at' => now(),
            'status' => 'active',
        ]);
    }
}
