# 18 - اختبارات كشف الاحتيال (Testing Complete)

## Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Events\PinAttemptFailed;
use App\Models\FlaggedTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class FraudDetectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wallet $wallet;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'pin_code' => Hash::make('1234'),
            'status' => 'active',
        ]);
        $this->wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'currency' => 'USD',
            'balance' => 10000,
        ]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_flags_high_amount_transactions()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963900000002',
                'amount' => 6000,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('errors.fraud.0', fn($msg) => str_contains($msg, 'حظر'));
    }

    /** @test */
    public function it_locks_pin_after_5_failed_attempts()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->withToken($this->token)
                ->postJson('/api/v1/transfer', [
                    'to_phone' => '963900000002',
                    'amount' => 10,
                    'currency' => 'USD',
                    'pin' => '0000',
                ]);
        }

        // المحاولة السادسة
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963900000002',
                'amount' => 10,
                'currency' => 'USD',
                'pin' => '0000',
            ]);

        $response->assertStatus(429)
            ->assertJsonPath('errors.pin.0', fn($msg) => str_contains($msg, 'قفل'));
    }

    /** @test */
    public function it_creates_flagged_transaction_record()
    {
        $this->withToken($this->token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963900000002',
                'amount' => 6000,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        $this->assertDatabaseHas('flagged_transactions', [
            'user_id' => $this->user->id,
            'amount' => 6000,
        ]);
    }

    /** @test */
    public function it_dispatches_pin_attempt_event()
    {
        Event::fake();

        $this->withToken($this->token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963900000002',
                'amount' => 10,
                'currency' => 'USD',
                'pin' => '0000',
            ]);

        Event::assertDispatched(PinAttemptFailed::class);
    }

    /** @test */
    public function it_allows_admin_to_approve_flagged_transaction()
    {
        $flagged = FlaggedTransaction::create([
            'user_id' => $this->user->id,
            'amount' => 6000,
            'currency' => 'USD',
            'triggered_rules' => [['rule' => 'high_amount', 'message' => 'مبلغ كبير']],
            'risk_score' => 60,
            'status' => 'pending',
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $adminToken = JWTAuth::fromUser($admin);

        $response = $this->withToken($adminToken)
            ->postJson("/api/v1/admin/fraud/{$flagged->id}/approve");

        $response->assertStatus(200);
        $this->assertEquals('approved', $flagged->fresh()->status);
    }
}
```

## تشغيل الاختبارات

```bash
php artisan test --filter=FraudDetectionTest
```
