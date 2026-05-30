<?php

declare(strict_types=1);

namespace Modules\Ledger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Ledger\Models\JournalEntry;

class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'journal_id' => (string) Str::ulid(),
            'entry_date' => now(),
            'description' => $this->faker->sentence,
            'reference_type' => $this->faker->randomElement(['transaction', 'settlement', 'fee', 'reversal']),
            'reference_id' => (string) Str::ulid(),
            'total_amount' => $this->faker->numberBetween(100, 10000000),
            'currency' => 'SYP',
            'metadata' => null,
            'posted_at' => null,
        ];
    }

    public function posted(): static
    {
        return $this->state(fn() => ['posted_at' => now()]);
    }
}
