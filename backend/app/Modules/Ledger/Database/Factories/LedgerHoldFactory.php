<?php

declare(strict_types=1);

namespace Modules\Ledger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Ledger\Models\LedgerHold;

final class LedgerHoldFactory extends Factory
{
    protected $model = LedgerHold::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'account_id' => (string) Str::ulid(),
            'amount' => $this->faker->numberBetween(100, 10000000),
            'currency' => 'SYP',
            'status' => 'active',
            'reason' => $this->faker->sentence,
            'expires_at' => now()->addHours(24),
            'released_at' => null,
            'release_reason' => null,
            'metadata' => null,
        ];
    }

    public function released(): static
    {
        return $this->state(fn() => [
            'status' => 'released',
            'released_at' => now(),
        ]);
    }
}
