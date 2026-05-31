<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Database\Factories;

use App\Modules\Remittance\Models\Remittance;
use Illuminate\Database\Eloquent\Factories\Factory;

final class RemittanceFactory extends Factory
{
    protected $model = Remittance::class;

    public function definition(): array
    {
        return [
            'sender_user_id' => (string) fake()->ulid(),
            'receiver_name' => fake()->name(),
            'receiver_phone' => fake()->phoneNumber(),
            'from_currency' => 'SYP',
            'to_currency' => 'USD',
            'from_amount_fils' => fake()->numberBetween(100_000, 10_000_000),
            'to_amount_fils' => fake()->numberBetween(10, 1000),
            'exchange_rate_id' => (string) fake()->ulid(),
            'rate_used_fils_per_unit' => 12500,
            'fee_fils' => fake()->numberBetween(1000, 50000),
            'total_charged_fils' => fake()->numberBetween(101_000, 10_050_000),
            'status' => 'pending',
            'reference_number' => 'REM-' . strtoupper(bin2hex(random_bytes(6))),
        ];
    }
}
