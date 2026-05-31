<?php

declare(strict_types=1);

namespace App\Modules\Agent\Database\Factories;

use App\Modules\Agent\Models\AgentWallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class AgentWalletFactory extends Factory
{
    protected $model = AgentWallet::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'agent_id' => Str::ulid()->toBase32(),
            'currency' => 'SYP',
            'balance' => 10000000,
            'float_balance' => 5000000,
            'daily_limit' => 5000000,
            'daily_used' => 0,
            'monthly_limit' => 150000000,
            'monthly_used' => 0,
            'status' => 'active',
        ];
    }
}
