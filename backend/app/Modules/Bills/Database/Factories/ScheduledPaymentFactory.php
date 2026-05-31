<?php

declare(strict_types=1);

namespace App\Modules\Bills\Database\Factories;

use App\Modules\BillProvider\Models\BillProvider;
use App\Modules\Bills\Models\ScheduledPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ScheduledPaymentFactory extends Factory
{
    protected $model = ScheduledPayment::class;

    public function definition(): array
    {
        $recurrence = $this->faker->randomElement(['monthly', 'quarterly', 'yearly']);
        $day = $this->faker->numberBetween(1, 28);

        return [
            'user_id' => User::factory(),
            'bill_provider_id' => BillProvider::factory(),
            'account_number' => 'ACC-' . $this->faker->unique()->numerify('########'),
            'amount_fils' => $this->faker->randomElement([50_000, 100_000, 250_000]),
            'recurrence' => $recurrence,
            'recurrence_day' => $day,
            'next_execution_date' => now()->addDays(rand(1, 30))->format('Y-m-d'),
            'last_executed_at' => null,
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
