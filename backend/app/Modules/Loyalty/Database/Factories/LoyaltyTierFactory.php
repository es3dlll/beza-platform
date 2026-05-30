<?php

declare(strict_types=1);

namespace Modules\Loyalty\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Loyalty\Models\LoyaltyTier;

final class LoyaltyTierFactory extends Factory
{
    protected $model = LoyaltyTier::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->randomElement(['Bronze', 'Silver', 'Gold', 'Platinum']),
            'slug' => $this->faker->unique()->slug(1),
            'min_points' => 0,
            'max_points' => $this->faker->numberBetween(1000, 100000),
            'multiplier' => 1.0,
            'color' => $this->faker->hexColor,
            'benefits' => json_encode(['fee_waiver' => false, 'priority_support' => false]),
        ];
    }
}
