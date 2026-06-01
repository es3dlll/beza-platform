# 18 - اختبارات 2FA (Testing Complete)

## Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('1234'),
        ]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_enables_two_factor()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/2fa/enable', [
                'password' => 'password',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'data' => ['secret', 'qr_code_url', 'qr_code_svg'],
            ]);
    }

    /** @test */
    public function it_verifies_two_factor_code()
    {
        // Enable first
        $enableRes = $this->withToken($this->token)
            ->postJson('/api/v1/2fa/enable', ['password' => 'password']);

        $secret = $enableRes['data']['secret'];

        // Generate valid TOTP code
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $code = $google2fa->getCurrentOtp($secret);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/2fa/verify', ['code' => $code]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify it's enabled
        $this->assertNotNull($this->user->fresh()->two_factor_confirmed_at);
    }

    /** @test */
    public function it_requires_2fa_for_large_transfers()
    {
        // Enable 2FA for user
        $this->enableTwoFactorForUser();

        // Try transfer > 1000 USD without 2FA code
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963900000002',
                'amount' => 1500,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        $response->assertStatus(402)
            ->assertJson(['requires_2fa' => true]);
    }

    /** @test */
    public function it_accepts_recovery_code()
    {
        $this->enableTwoFactorForUser();
        $codes = $this->user->fresh()->recoveryCodes();

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/2fa/verify', ['code' => $codes[0]]);

        $response->assertStatus(200);

        // Verify the code was consumed
        $this->assertCount(
            count($codes) - 1,
            $this->user->fresh()->recoveryCodes()
        );
    }

    /** @test */
    public function it_rejects_invalid_code()
    {
        $this->enableTwoFactorForUser();

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/2fa/verify', ['code' => '000000']);

        $response->assertStatus(422);
    }

    private function enableTwoFactorForUser(): void
    {
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $secret = $google2fa->generateSecretKey();
        $code = $google2fa->getCurrentOtp($secret);

        $this->user->setTwoFactorSecret($secret);
        $this->user->setRecoveryCodes(['CODE1-AAAA-BBBB', 'CODE2-CCCC-DDDD']);
        $this->user->confirmTwoFactor();
    }
}
```

## تشغيل الاختبارات

```bash
php artisan test --filter=TwoFactorTest
```
