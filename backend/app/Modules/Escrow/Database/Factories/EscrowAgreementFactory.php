<?php

declare(strict_types=1);

namespace Modules\Escrow\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Escrow\Models\EscrowAgreement;

final class EscrowAgreementFactory extends Factory
{
    protected $model = EscrowAgreement::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'buyer_id' => (string) Str::ulid(),
            'seller_id' => (string) Str::ulid(),
            'reference_type' => $this->faker->randomElement(['order', 'contract', 'service']),
            'reference_id' => (string) Str::ulid(),
            'total_amount' => $this->faker->numberBetween(10000, 10000000),
            'fee_amount' => $this->faker->numberBetween(100, 100000),
            'net_amount' => $this->faker->numberBetween(9900, 9900000),
            'currency' => 'SYP',
            'status' => 'pending',
            'description' => $this->faker->sentence(),
            'expires_at' => now()->addDays(30),
        ];
    }

    public function held(): static
    {
        return $this->state(fn() => [
            'status' => 'held',
            'cfe_hold_id' => (string) Str::ulid(),
        ]);
    }
}
