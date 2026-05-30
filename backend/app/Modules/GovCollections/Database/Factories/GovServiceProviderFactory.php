<?php

declare(strict_types=1);

namespace Modules\GovCollections\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\GovCollections\Models\GovServiceProvider;

final class GovServiceProviderFactory extends Factory
{
    protected $model = GovServiceProvider::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->company,
            'code' => $this->faker->unique()->word,
            'type' => $this->faker->randomElement(['tax', 'customs', 'license', 'fee', 'fine']),
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
