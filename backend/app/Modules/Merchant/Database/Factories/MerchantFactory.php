<?php

declare(strict_types=1);

namespace Modules\Merchant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Merchant\Models\Merchant;

class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'business_name' => $this->faker->company,
            'business_type' => $this->faker->randomElement(['retail', 'food', 'services', 'transport']),
            'registration_number' => $this->faker->numerify('REG-########'),
            'tax_number' => $this->faker->numerify('TAX-########'),
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
            'status' => 'pending',
            'metadata' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn() => [
            'is_approved' => true,
            'approved_at' => now(),
            'status' => 'active',
        ]);
    }
}
