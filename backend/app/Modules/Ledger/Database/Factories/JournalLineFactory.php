<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Database\Factories;

use App\Modules\Ledger\Models\JournalLine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class JournalLineFactory extends Factory
{
    protected $model = JournalLine::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'type' => fake()->randomElement(['debit', 'credit']),
            'amount' => fake()->numberBetween(100, 100000),
            'currency' => 'SYP',
        ];
    }
}
