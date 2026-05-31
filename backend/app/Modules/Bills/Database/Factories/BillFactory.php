<?php

declare(strict_types=1);

namespace App\Modules\Bills\Database\Factories;

use App\Modules\BillProvider\Models\BillProvider;
use App\Modules\Bills\Models\Bill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bill_provider_id' => BillProvider::factory(),
            'account_number' => 'ACC-' . $this->faker->unique()->numerify('########'),
            'amount_fils' => $this->faker->randomElement([50_000, 100_000, 250_000, 500_000, 1_000_000]),
            'due_date' => $this->faker->dateTimeBetween('-10 days', '+30 days')->format('Y-m-d'),
            'status' => 'pending',
            'paid_at' => null,
            'receipt_reference' => null,
            'metadata' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn(array $attrs) => [
            'status' => 'paid',
            'paid_at' => now(),
            'receipt_reference' => 'RCP-' . strtoupper(bin2hex(random_bytes(4))),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn(array $attrs) => [
            'due_date' => now()->subDays(rand(1, 15))->format('Y-m-d'),
        ]);
    }
}
