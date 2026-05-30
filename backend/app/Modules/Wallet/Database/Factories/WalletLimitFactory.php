<?php

declare(strict_types=1);

namespace Modules\Wallet\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Wallet\Models\WalletLimit;

final class WalletLimitFactory extends Factory
{
    protected $model = WalletLimit::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'wallet_type' => $this->faker->randomElement(['personal', 'agent', 'merchant']),
            'tier' => $this->faker->randomElement(['tier_1_basic', 'tier_2_standard', 'tier_3_premium']),
            'currency' => 'SYP',
            'daily_max' => 5000000,
            'monthly_max' => 50000000,
            'per_txn_max' => 1000000,
            'min_balance' => 0,
            'max_balance' => 10000000,
        ];
    }
}
