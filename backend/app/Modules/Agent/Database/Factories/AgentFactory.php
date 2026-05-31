<?php

declare(strict_types=1);

namespace App\Modules\Agent\Database\Factories;

use App\Modules\Agent\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'user_id' => Str::ulid()->toBase32(),
            'phone' => '9639' . fake()->numerify('#######'),
            'name' => fake()->name,
            'name_ar' => 'وكيل تجريبي',
            'kyc_tier' => 't1',
            'status' => 'active',
            'is_verified' => true,
            'verified_at' => now(),
        ];
    }
}
