<?php

declare(strict_types=1);

namespace Modules\FX\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\FX\Models\FxQuote;

class FxQuoteFactory extends Factory
{
    protected $model = FxQuote::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'base_currency' => 'SYP',
            'quote_currency' => $this->faker->randomElement(['USD', 'EUR', 'TRY']),
            'amount' => $this->faker->numberBetween(1000, 10000000),
            'converted_amount' => $this->faker->numberBetween(1, 100000),
            'rate' => $this->faker->randomFloat(4, 0.01, 10000),
            'expires_at' => now()->addMinutes(5),
            'status' => 'active',
        ];
    }
}
