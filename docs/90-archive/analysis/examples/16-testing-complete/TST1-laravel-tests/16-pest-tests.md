# 16 - اختبارات Pest (Pest Tests)

## تثبيت Pest

```bash
composer require pestphp/pest --dev
php artisan pest:install
```

## Pest Tests للتحويلات

```php
<?php

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->sender = User::factory()->create([
        'phone' => '963944123456',
        'pin_code' => Hash::make('1234'),
        'status' => 'active',
    ]);
    $this->receiver = User::factory()->create([
        'phone' => '963944654321',
        'status' => 'active',
    ]);

    Wallet::factory()->create([
        'user_id' => $this->sender->id, 'currency' => 'USD', 'balance' => 500,
    ]);
    Wallet::factory()->create([
        'user_id' => $this->receiver->id, 'currency' => 'USD', 'balance' => 0,
    ]);

    $this->token = JWTAuth::fromUser($this->sender);
});

test('successful transfer', function () {
    \Pest\Laravel\postJson('/api/v1/transfer', [
        'to_phone' => '963944654321',
        'amount' => 100,
        'currency' => 'USD',
        'pin' => '1234',
    ], ['Authorization' => 'Bearer ' . $this->token])
        ->assertStatus(201)
        ->assertJson(['success' => true]);
});

test('rejects self transfer', function () {
    \Pest\Laravel\postJson('/api/v1/transfer', [
        'to_phone' => '963944123456',
        'amount' => 100,
        'currency' => 'USD',
        'pin' => '1234',
    ], ['Authorization' => 'Bearer ' . $this->token])
        ->assertStatus(422);
});

test('rejects invalid pin', function () {
    \Pest\Laravel\postJson('/api/v1/transfer', [
        'to_phone' => '963944654321',
        'amount' => 100,
        'currency' => 'USD',
        'pin' => '0000',
    ], ['Authorization' => 'Bearer ' . $this->token])
        ->assertStatus(422);
});

test('rejects insufficient balance', function () {
    \Pest\Laravel\postJson('/api/v1/transfer', [
        'to_phone' => '963944654321',
        'amount' => 999999,
        'currency' => 'USD',
        'pin' => '1234',
    ], ['Authorization' => 'Bearer ' . $this->token])
        ->assertStatus(422);
});

test('requires authentication', function () {
    \Pest\Laravel\postJson('/api/v1/transfer', [
        'to_phone' => '963944654321',
        'amount' => 100,
        'currency' => 'USD',
        'pin' => '1234',
    ])->assertStatus(401);
});
```

## تشغيل Pest

```bash
./vendor/bin/pest
./vendor/bin/pest --filter=TransferPestTest
./vendor/bin/pest --parallel
```
