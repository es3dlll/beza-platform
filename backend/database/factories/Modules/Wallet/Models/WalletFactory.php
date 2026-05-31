<?php

declare(strict_types=1);

namespace Database\Factories\Modules\Wallet\Models;

use App\Models\User;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

final class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance_fils' => 0,
            'currency' => 'SYP',
        ];
    }
}
