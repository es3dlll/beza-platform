<?php

declare(strict_types=1);

namespace Modules\Wallet\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Wallet\Models\Wallet;

final class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'wallet_type' => $this->faker->randomElement(['personal', 'agent', 'merchant']),
            'currency' => 'SYP',
            'balance' => 0,
            'status' => 'active',
            'daily_limit' => 5000000,
            'monthly_limit' => 50000000,
            'max_balance' => 10000000,
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => ['status' => 'active']);
    }

    public function withBalance(int $amount): static
    {
        return $this->state(fn() => ['balance' => $amount]);
    }
}
