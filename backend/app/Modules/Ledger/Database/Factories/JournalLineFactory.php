<?php

declare(strict_types=1);

namespace Modules\Ledger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Ledger\Models\JournalLine;

final class JournalLineFactory extends Factory
{
    protected $model = JournalLine::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'journal_entry_id' => (string) Str::ulid(),
            'account_id' => (string) Str::ulid(),
            'amount' => $this->faker->numberBetween(100, 10000000),
            'type' => $this->faker->randomElement(['debit', 'credit']),
            'currency' => 'SYP',
            'description' => $this->faker->sentence,
        ];
    }
}
