<?php

declare(strict_types=1);

namespace Modules\Remittance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Remittance\Models\Corridor;

final class CorridorFactory extends Factory
{
    protected $model = Corridor::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'from_currency' => $this->faker->randomElement(['SYP', 'USD', 'EUR', 'TRY']),
            'to_currency' => $this->faker->randomElement(['SYP', 'USD', 'EUR', 'TRY']),
            'from_country' => $this->faker->randomElement(['SY', 'TR', 'DE', 'US', 'AE']),
            'to_country' => $this->faker->randomElement(['SY', 'TR', 'DE', 'US', 'AE']),
            'is_active' => true,
        ];
    }
}
