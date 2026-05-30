<?php

declare(strict_types=1);

namespace Modules\Ledger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Ledger\Models\LedgerAccount;

final class LedgerAccountFactory extends Factory
{
    protected $model = LedgerAccount::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'account_number' => $this->faker->unique()->numerify('####-####'),
            'name' => $this->faker->company,
            'type' => $this->faker->randomElement(['asset', 'liability', 'equity', 'income', 'expense']),
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
            'status' => 'active',
            'metadata' => null,
        ];
    }

    public function asset(): static
    {
        return $this->state(fn() => ['type' => 'asset']);
    }

    public function liability(): static
    {
        return $this->state(fn() => ['type' => 'liability']);
    }

    public function withBalance(int $amount): static
    {
        return $this->state(fn() => [
            'balance' => $amount,
            'available_balance' => $amount,
        ]);
    }
}
