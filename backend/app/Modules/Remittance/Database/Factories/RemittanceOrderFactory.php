<?php

declare(strict_types=1);

namespace Modules\Remittance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Remittance\Models\RemittanceOrder;

final class RemittanceOrderFactory extends Factory
{
    protected $model = RemittanceOrder::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'corridor_id' => (string) Str::ulid(),
            'beneficiary_id' => (string) Str::ulid(),
            'source_amount' => $this->faker->numberBetween(10000, 5000000),
            'source_currency' => 'SYP',
            'destination_amount' => $this->faker->numberBetween(1, 5000),
            'destination_currency' => $this->faker->randomElement(['USD', 'EUR', 'TRY']),
            'rate' => $this->faker->randomFloat(4, 0.01, 10000),
            'fee' => $this->faker->numberBetween(500, 50000),
            'total_charge' => $this->faker->numberBetween(10500, 5050000),
            'payment_method' => $this->faker->randomElement(['wallet', 'bank', 'cash']),
            'status' => 'pending',
            'reference' => (string) Str::ulid(),
            'metadata' => null,
        ];
    }
}
