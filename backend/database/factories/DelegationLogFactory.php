<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Team\Models\DelegationLog;
use App\Modules\Team\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class DelegationLogFactory extends Factory
{
    protected $model = DelegationLog::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'granter_id' => User::factory(),
            'grantee_id' => User::factory(),
            'permissions' => ['agents:view', 'agents:commissions'],
            'action' => 'granted',
            'reason' => fake()->sentence(),
        ];
    }
}
