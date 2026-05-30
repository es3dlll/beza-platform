<?php

declare(strict_types=1);

namespace Modules\Ledger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Ledger\Models\LedgerTransaction;

final class LedgerTransactionFactory extends Factory
{
    protected $model = LedgerTransaction::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'uuid' => (string) Str::ulid(),
            'journal_entry_id' => (string) Str::ulid(),
            'type' => $this->faker->randomElement(['debit', 'credit']),
            'status' => 'completed',
            'transactionable_type' => null,
            'transactionable_id' => null,
            'metadata' => null,
        ];
    }
}
