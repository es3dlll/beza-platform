<?php

declare(strict_types=1);

namespace Modules\Loyalty\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Loyalty\Models\LoyaltyPoints;

class LoyaltyPointsFactory extends Factory
{
    protected $model = LoyaltyPoints::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'balance' => 0,
            'lifetime_points' => 0,
            'tier_id' => (string) Str::ulid(),
        ];
    }
}
