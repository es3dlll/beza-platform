<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\CoreFinancialEngine\Models\CfeTransaction;

final class CfeTransactionFactory extends Factory
{
    protected $model = CfeTransaction::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'uuid' => (string) Str::ulid(),
            'reference_type' => $this->faker->randomElement(['transaction', 'settlement', 'fee', 'reversal']),
            'reference_id' => (string) Str::ulid(),
            'type' => $this->faker->randomElement(['debit', 'credit']),
            'status' => 'completed',
            'total_amount' => $this->faker->numberBetween(100, 10000000),
            'currency' => 'SYP',
            'description' => $this->faker->sentence,
            'metadata' => null,
            'reversal_id' => null,
            'reversed_at' => null,
        ];
    }
}
