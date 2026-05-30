<?php

declare(strict_types=1);

namespace Modules\Agent\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Agent\Models\AgentTransaction;

class AgentTransactionFactory extends Factory
{
    protected $model = AgentTransaction::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'agent_id' => (string) Str::ulid(),
            'wallet_id' => (string) Str::ulid(),
            'type' => $this->faker->randomElement(['cash_in', 'cash_out', 'commission']),
            'amount' => $this->faker->numberBetween(1000, 5000000),
            'currency' => 'SYP',
            'fee' => 0,
            'reference' => (string) Str::ulid(),
            'description' => $this->faker->sentence,
            'status' => 'completed',
            'metadata' => null,
        ];
    }
}
