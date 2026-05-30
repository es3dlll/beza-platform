<?php

declare(strict_types=1);

namespace Modules\Float\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Float\Models\FloatAccount;

final class FloatAccountFactory extends Factory
{
    protected $model = FloatAccount::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'agent_id' => (string) Str::ulid(),
            'currency' => 'SYP',
            'balance' => 0,
            'threshold_low' => 50000,
            'threshold_high' => 5000000,
            'status' => 'active',
            'last_replenished_at' => null,
        ];
    }
}
