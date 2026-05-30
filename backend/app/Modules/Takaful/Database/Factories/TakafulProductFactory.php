<?php

declare(strict_types=1);

namespace Modules\Takaful\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Takaful\Models\TakafulProduct;

final class TakafulProductFactory extends Factory
{
    protected $model = TakafulProduct::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->word() . ' Takaful',
            'name_ar' => 'تكافل ' . $this->faker->word(),
            'type' => $this->faker->randomElement(['family', 'car', 'health', 'property', 'life']),
            'description' => $this->faker->sentence(),
            'description_ar' => $this->faker->sentence(),
            'min_premium' => $this->faker->numberBetween(1000, 10000),
            'max_premium' => $this->faker->numberBetween(50000, 500000),
            'coverage_amount' => $this->faker->numberBetween(500000, 5000000),
            'waiting_days' => $this->faker->randomElement([0, 30, 90]),
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
