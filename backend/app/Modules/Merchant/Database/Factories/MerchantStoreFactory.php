<?php

declare(strict_types=1);

namespace Modules\Merchant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Merchant\Models\MerchantStore;

final class MerchantStoreFactory extends Factory
{
    protected $model = MerchantStore::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'merchant_id' => (string) Str::ulid(),
            'name' => $this->faker->company,
            'store_type' => $this->faker->randomElement(['physical', 'online']),
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'governorate' => $this->faker->randomElement(['دمشق', 'حلب', 'حمص']),
            'is_active' => true,
        ];
    }
}
