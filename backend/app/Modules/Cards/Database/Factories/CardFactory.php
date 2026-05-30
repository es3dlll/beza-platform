<?php

declare(strict_types=1);

namespace Modules\Cards\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Cards\Models\Card;

class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'wallet_id' => (string) Str::ulid(),
            'card_type' => $this->faker->randomElement(['debit', 'prepaid', 'virtual']),
            'card_network' => $this->faker->randomElement(['Visa', 'Mastercard', 'Local']),
            'masked_pan' => '**** **** **** ' . $this->faker->numerify('####'),
            'expiry_month' => $this->faker->numberBetween(1, 12),
            'expiry_year' => now()->addYears(4)->year,
            'status' => 'active',
            'activated_at' => now(),
            'suspended_at' => null,
            'cancelled_at' => null,
            'metadata' => null,
        ];
    }
}
