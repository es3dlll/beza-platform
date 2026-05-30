<?php

declare(strict_types=1);

namespace Modules\Wallet\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Wallet\Models\WalletTransaction;

final class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'wallet_id' => (string) Str::ulid(),
            'type' => $this->faker->randomElement(['deposit', 'withdrawal', 'transfer', 'payment']),
            'amount' => $this->faker->numberBetween(100, 10000000),
            'currency' => 'SYP',
            'fee' => 0,
            'reference' => (string) Str::ulid(),
            'reverse_reference' => null,
            'description' => $this->faker->sentence,
            'metadata' => null,
        ];
    }
}
