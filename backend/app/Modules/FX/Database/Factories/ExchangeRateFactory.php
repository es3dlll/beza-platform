<?php

declare(strict_types=1);

namespace App\Modules\FX\Database\Factories;

use App\Modules\FX\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    public function definition(): array
    {
        return [
            'from_currency' => 'SYP',
            'to_currency' => 'USD',
            'rate_fils_per_unit' => 12500,
            'bid_fils_per_unit' => 12400,
            'ask_fils_per_unit' => 12600,
            'provider' => 'simulated',
            'valid_from' => now(),
            'valid_until' => now()->addMinutes(5),
            'is_active' => true,
        ];
    }
}
