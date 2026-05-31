<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Database\Factories;

use App\Modules\Ledger\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'transaction_id' => Str::ulid()->toBase32(),
            'description' => fake()->sentence(),
            'description_ar' => fake()->sentence(),
            'hash' => hash('sha256', Str::random(40)),
        ];
    }
}
