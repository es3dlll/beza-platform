<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'user_id' => User::factory(),
            'currency' => 'SYP',
            'balance' => 1000000,
            'status' => 'active',
        ];
    }
}
