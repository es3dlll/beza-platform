<?php

declare(strict_types=1);

namespace App\Modules\Agent\Database\Factories;

use App\Modules\Agent\Models\AgentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class AgentTransactionFactory extends Factory
{
    protected $model = AgentTransaction::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'agent_id' => Str::ulid()->toBase32(),
            'type' => 'cash_in',
            'status' => 'completed',
            'customer_wallet_id' => Str::ulid()->toBase32(),
            'customer_phone' => '9639' . fake()->numerify('#######'),
            'amount' => 100000,
            'currency' => 'SYP',
            'commission_amount' => 1000,
            'commission_rate_bps' => 100,
        ];
    }
}
