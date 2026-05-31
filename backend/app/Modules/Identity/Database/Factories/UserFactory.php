<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Factories;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'phone' => '9639' . fake()->numerify('#######'),
            'name' => fake()->name(),
            'name_ar' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'pin_hash' => bcrypt('1234'),
            'status' => 'active',
            'kyc_tier' => 't1',
            'device_id' => Str::ulid()->toBase32(),
        ];
    }
}
