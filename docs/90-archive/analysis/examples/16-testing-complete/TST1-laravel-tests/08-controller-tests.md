# 08 - اختبارات المتحكمات (Controller Tests)

## AuthTest

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'مستخدم جديد',
            'phone' => '963900000001',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'pin' => '1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'data' => ['token', 'user'],
            ]);

        $this->assertDatabaseHas('users', ['phone' => '963900000001']);
        $this->assertDatabaseCount('wallets', 2); // SYP + USD
    }

    /** @test */
    public function user_cannot_register_with_existing_phone()
    {
        User::factory()->create(['phone' => '963900000001']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'مستخدم',
            'phone' => '963900000001',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'pin' => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function user_can_login()
    {
        User::factory()->create([
            'phone' => '963900000001',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963900000001',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['token']]);
    }

    /** @test */
    public function suspended_user_cannot_login()
    {
        User::factory()->create([
            'phone' => '963900000001',
            'password' => Hash::make('password'),
            'status' => 'suspended',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963900000001',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
    }
}
```

## TransferTest

```php
class TransferTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;
    private User $receiver;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sender = User::factory()->create(['pin_code' => Hash::make('1234')]);
        $this->receiver = User::factory()->create();

        Wallet::factory()->create([
            'user_id' => $this->sender->id, 'currency' => 'USD', 'balance' => 1000
        ]);
        Wallet::factory()->create([
            'user_id' => $this->sender->id, 'currency' => 'SYP', 'balance' => 100000
        ]);
        Wallet::factory()->create([
            'user_id' => $this->receiver->id, 'currency' => 'USD', 'balance' => 0
        ]);
        Wallet::factory()->create([
            'user_id' => $this->receiver->id, 'currency' => 'SYP', 'balance' => 0
        ]);

        $this->token = JWTAuth::fromUser($this->sender);
    }

    /** @test */
    public function completes_successful_transfer()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => $this->receiver->phone,
                'amount' => 100,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        $response->assertStatus(201);
        $this->assertEquals(900, $this->sender->usdWallet->fresh()->balance);
        $this->assertEquals(100, $this->receiver->usdWallet->fresh()->balance);
    }

    /** @test */
    public function fails_for_self_transfer()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => $this->sender->phone,
                'amount' => 100,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function fails_with_insufficient_balance()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => $this->receiver->phone,
                'amount' => 99999,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function fails_with_wrong_pin()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => $this->receiver->phone,
                'amount' => 100,
                'currency' => 'USD',
                'pin' => '0000',
            ]);

        $response->assertStatus(422);
    }
}
```
