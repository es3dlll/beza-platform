# 13 - اختبارات الاستثناءات (Exception Tests)

```php
<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidPinException;
use App\Exceptions\SelfTransferException;
use App\Exceptions\DailyLimitExceededException;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ExceptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_422_for_insufficient_balance()
    {
        $user = User::factory()->create(['pin_code' => Hash::make('1234')]);
        Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 10]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963900000002',
                'amount' => 100,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'رصيد غير كافٍ',
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_pin()
    {
        $user = User::factory()->create(['pin_code' => Hash::make('1234')]);
        Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 1000]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963900000002',
                'amount' => 100,
                'currency' => 'USD',
                'pin' => 'wrong',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'رمز PIN غير صحيح',
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_recipient()
    {
        $user = User::factory()->create(['pin_code' => Hash::make('1234')]);
        Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 1000]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963900009999',
                'amount' => 100,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_requests()
    {
        $response = $this->postJson('/api/v1/transfer', [
            'to_phone' => '963900000002',
            'amount' => 100,
            'currency' => 'USD',
            'pin' => '1234',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->postJson('/api/v1/transfer', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_phone', 'amount', 'currency', 'pin']);
    }
}
```
