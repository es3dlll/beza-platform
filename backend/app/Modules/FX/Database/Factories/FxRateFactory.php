<?php

declare(strict_types=1);

namespace Modules\FX\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\FX\Models\FxRate;

class FxRateFactory extends Factory
{
    protected $model = FxRate::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'base_currency' => 'SYP',
            'quote_currency' => $this->faker->randomElement(['USD', 'EUR', 'TRY', 'AED', 'SAR']),
            'rate' => $this->faker->randomFloat(4, 0.01, 10000),
            'bid' => $this->faker->randomFloat(4, 0.01, 10000),
            'ask' => $this->faker->randomFloat(4, 0.01, 10000),
            'source' => 'CBS',
            'is_active' => true,
        ];
    }
}
