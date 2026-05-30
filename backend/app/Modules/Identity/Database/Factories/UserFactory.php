<?php

declare(strict_types=1);

namespace Modules\Identity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Identity\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'phone' => $this->faker->numerify('9627########'),
            'phone_country_code' => '962',
            'status' => 'pending',
            'kyc_tier' => 'tier_1_basic',
            'locale' => 'ar',
            'failed_attempts' => 0,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn(array $attributes) => [
            'phone_verified_at' => now(),
            'status' => 'active',
        ]);
    }

    public function withPin(string $pin = '123456'): static
    {
        return $this->state(fn(array $attributes) => [
            'pin_hash' => bcrypt($pin),
            'pin_updated_at' => now(),
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn(array $attributes) => [
            'locked_until' => now()->addMinutes(30),
            'failed_attempts' => 5,
        ]);
    }
}
