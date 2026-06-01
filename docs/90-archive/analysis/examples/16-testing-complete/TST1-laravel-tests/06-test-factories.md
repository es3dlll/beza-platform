# 06 - مصانع البيانات (Test Factories)

## UserFactory

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'name' => fake('ar_SA')->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '9639' . fake()->unique()->numerify('########'),
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('1234'),
            'status' => 'active',
            'kyc_status' => 'verified',
            'is_admin' => false,
            'is_merchant' => false,
            'is_agent' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'kyc_status' => 'not_submitted',
        ]);
    }
}
```

## WalletFactory

```php
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        $currency = fake()->randomElement(['SYP', 'USD']);
        return [
            'user_id' => User::factory(),
            'currency' => $currency,
            'wallet_number' => ($currency === 'SYP' ? '62' : '63')
                . fake()->unique()->numerify('##########'),
            'balance' => fake()->randomFloat(2, 0, 10000),
            'frozen_balance' => 0,
            'is_active' => true,
        ];
    }

    public function syp(): static
    {
        return $this->state(fn(array $attributes) => [
            'currency' => 'SYP',
            'wallet_number' => '62' . fake()->unique()->numerify('##########'),
        ]);
    }

    public function usd(): static
    {
        return $this->state(fn(array $attributes) => [
            'currency' => 'USD',
            'wallet_number' => '63' . fake()->unique()->numerify('##########'),
        ]);
    }

    public function withBalance(float $amount): static
    {
        return $this->state(fn(array $attributes) => [
            'balance' => $amount,
        ]);
    }
}
```

## TransactionFactory

```php
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'from_wallet_id' => Wallet::factory(),
            'to_wallet_id' => Wallet::factory(),
            'amount' => fake()->randomFloat(2, 1, 1000),
            'amount_in_usd' => fake()->randomFloat(2, 1, 1000),
            'type' => 'transfer',
            'status' => 'completed',
            'reference_number' => 'BZ' . now()->format('ymdHis') . strtoupper(Str::random(6)),
            'fee' => 0,
            'completed_at' => now(),
        ];
    }
}
```
