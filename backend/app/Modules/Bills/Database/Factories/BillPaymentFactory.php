<?php

declare(strict_types=1);

namespace Modules\Bills\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Bills\Models\BillPayment;

final class BillPaymentFactory extends Factory
{
    protected $model = BillPayment::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'provider_id' => (string) Str::ulid(),
            'account_number' => $this->faker->numerify('##############'),
            'amount' => $this->faker->numberBetween(1000, 500000),
            'currency' => 'SYP',
            'fee' => $this->faker->numberBetween(100, 5000),
            'reference' => (string) Str::ulid(),
            'status' => 'completed',
            'paid_at' => now(),
            'metadata' => null,
        ];
    }
}
