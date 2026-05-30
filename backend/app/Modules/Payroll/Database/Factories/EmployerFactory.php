<?php

declare(strict_types=1);

namespace Modules\Payroll\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Payroll\Models\Employer;

final class EmployerFactory extends Factory
{
    protected $model = Employer::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'company_name' => $this->faker->company,
            'registration_number' => $this->faker->numerify('CR-########'),
            'is_approved' => false,
            'status' => 'pending',
            'metadata' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn() => [
            'is_approved' => true,
            'status' => 'active',
        ]);
    }
}
