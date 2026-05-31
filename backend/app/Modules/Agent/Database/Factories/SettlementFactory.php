<?php

declare(strict_types=1);

namespace App\Modules\Agent\Database\Factories;

use App\Modules\Agent\Models\Settlement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'agent_id' => Str::ulid()->toBase32(),
            'settlement_date' => now()->subDay()->toDateString(),
            'expected_amount' => 5000000,
            'actual_amount' => 5000000,
            'difference' => 0,
            'commission_amount' => 50000,
            'status' => 'confirmed',
            'settled_at' => now(),
        ];
    }
}
