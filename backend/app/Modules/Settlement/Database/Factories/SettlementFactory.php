<?php

declare(strict_types=1);

namespace Modules\Settlement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Settlement\Models\Settlement;

final class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'batch_id' => $this->faker->unique()->numerify('BATCH-####'),
            'status' => 'pending',
            'total_amount' => $this->faker->numberBetween(10000, 100000000),
            'currency' => 'SYP',
            'settled_at' => null,
            'metadata' => null,
        ];
    }
}
