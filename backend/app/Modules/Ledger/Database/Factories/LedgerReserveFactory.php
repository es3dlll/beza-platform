<?php

declare(strict_types=1);

namespace Modules\Ledger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Ledger\Models\LedgerReserve;

class LedgerReserveFactory extends Factory
{
    protected $model = LedgerReserve::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'account_id' => (string) Str::ulid(),
            'amount' => $this->faker->numberBetween(1000, 100000000),
            'currency' => 'SYP',
            'status' => 'active',
            'reason' => $this->faker->sentence,
            'released_at' => null,
            'metadata' => null,
        ];
    }
}
