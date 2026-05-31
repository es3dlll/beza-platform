<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Database\Factories;

use App\Modules\Marketplace\Models\Product;
use App\Modules\Marketplace\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'seller_id' => Seller::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price_fils' => $this->faker->randomElement([50_000, 100_000, 250_000, 500_000, 1_000_000]),
            'category' => $this->faker->randomElement(['electronics', 'clothing', 'food', 'services', 'handicrafts']),
            'location' => $this->faker->city(),
            'images' => [$this->faker->imageUrl()],
            'status' => 'active',
            'rating' => $this->faker->randomFloat(1, 3, 5),
        ];
    }
}
