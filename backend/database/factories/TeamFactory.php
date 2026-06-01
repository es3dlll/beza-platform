<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Team\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Team',
            'owner_id' => User::factory(),
            'description' => fake()->sentence(),
            'max_depth' => 3,
            'status' => 'active',
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
