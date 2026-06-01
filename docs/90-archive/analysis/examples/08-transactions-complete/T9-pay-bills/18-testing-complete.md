# 18 - كل الاختبارات (Testing Complete)

## Feature Test

```php
<?php
// tests/Feature/PayBillsTest.php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PayBillsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'phone'    => '963944123456',
            'pin_code' => Hash::make('1234'),
            'status'   => 'active',
        ]);

        Wallet::factory()->create([
            'user_id'  => $this->user->id,
            'currency' => 'USD',
            'balance'  => 500.00,
            'is_active' => true,
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_completes_successfully()
    {
        $response = $this->withToken($this->token)->postJson('/bills/pay', [
            'amount'   => 100.00,
            'currency' => 'USD',
            'pin'      => '1234',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تمت العملية بنجاح',
            ]);
    }

    /** @test */
    public function it_fails_with_invalid_pin()
    {
        $response = $this->withToken($this->token)->postJson('/bills/pay', [
            'amount'   => 100.00,
            'currency' => 'USD',
            'pin'      => '9999',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_fails_with_insufficient_balance()
    {
        $response = $this->withToken($this->token)->postJson('/bills/pay', [
            'amount'   => 999999.00,
            'currency' => 'USD',
            'pin'      => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/bills/pay', [
            'amount'   => 100.00,
            'currency' => 'USD',
            'pin'      => '1234',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->withToken($this->token)->postJson('/bills/pay', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'currency', 'pin']);
    }
}
```

## تشغيل الاختبارات

```bash
php artisan test --filter=PayBillsTest
php artisan test --coverage
```
