<?php

declare(strict_types=1);

namespace Modules\Bills\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Bills\Models\BillProvider;

final class BillProviderFactory extends Factory
{
    protected $model = BillProvider::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $this->faker->company,
            'code' => $this->faker->unique()->word,
            'type' => $this->faker->randomElement(['electricity', 'water', 'telecom', 'internet', 'gas']),
            'is_active' => true,
            'supported_currencies' => ['SYP'],
            'metadata' => null,
        ];
    }
}
