<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Database\Factories;

use App\Models\User;
use App\Modules\Marketplace\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SellerFactory extends Factory
{
    protected $model = Seller::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_name' => 'متجر ' . $this->faker->company(),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['electronics', 'clothing', 'food', 'services', 'handicrafts']),
            'location' => $this->faker->city(),
            'contact_phone' => $this->faker->phoneNumber(),
            'status' => 'approved',
            'rating' => $this->faker->randomFloat(1, 3, 5),
            'total_sales' => $this->faker->numberBetween(0, 500),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => ['status' => 'pending', 'rating' => 0, 'total_sales' => 0]);
    }
}
