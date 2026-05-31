<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Database\Factories;

use App\Modules\Ledger\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class LedgerAccountFactory extends Factory
{
    protected $model = LedgerAccount::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'code' => fake()->unique()->numerify('1###'),
            'name' => fake()->company(),
            'name_ar' => fake()->company(),
            'type' => fake()->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense']),
            'balance' => 0,
            'currency' => 'SYP',
            'is_system' => false,
        ];
    }
}
