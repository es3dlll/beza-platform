<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Team\Models\Team;
use App\Modules\Team\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamMemberFactory extends Factory
{
    protected $model = TeamMember::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'role' => 'sub_agent',
            'level' => 1,
            'commission_rate' => 30.00,
            'daily_deposit_limit' => 3500000,
            'daily_withdrawal_limit' => 2100000,
            'status' => 'active',
            'activated_at' => now(),
        ];
    }

    public function master(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'master',
            'level' => 0,
            'commission_rate' => 60.00,
        ]);
    }

    public function junior(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'junior_sub_agent',
            'level' => 2,
            'commission_rate' => 10.00,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'activated_at' => null,
        ]);
    }
}
