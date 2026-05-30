<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\CoreFinancialEngine\Models\CfeTransactionLine;

class CfeTransactionLineFactory extends Factory
{
    protected $model = CfeTransactionLine::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'cfe_transaction_id' => (string) Str::ulid(),
            'account_id' => (string) Str::ulid(),
            'amount' => $this->faker->numberBetween(100, 10000000),
            'type' => $this->faker->randomElement(['debit', 'credit']),
            'currency' => 'SYP',
            'description' => $this->faker->sentence,
        ];
    }
}
