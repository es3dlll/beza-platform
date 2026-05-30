<?php

declare(strict_types=1);

namespace Modules\Marketplace\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Marketplace\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'name_ar' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => 'digital',
            'price' => fake()->numberBetween(1000, 100000),
            'stock' => -1,
            'is_active' => true,
        ];
    }
}
