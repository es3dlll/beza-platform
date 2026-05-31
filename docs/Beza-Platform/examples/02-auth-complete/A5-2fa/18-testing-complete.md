# 18 - كل الاختبارات — المصادقة الثنائية (2FA)

## Feature Test — TwoFactorTest

```php
<?php
// tests/Feature/TwoFactorTest.php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
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
        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_enables_two_factor()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/auth/2fa/enable');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['qr_code', 'secret']]);

        $this->assertNotNull($this->user->fresh()->two_factor_secret);
        $this->assertFalse($this->user->fresh()->two_factor_confirmed);
    }

    /** @test */
    public function it_verifies_two_factor()
    {
        // تفعيل 2FA أولاً
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $this->user->enableTwoFactor($secret);

        // توليد رمز صحيح
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/auth/2fa/verify', [
                'code' => $validCode,
            ]);

        $response->assertStatus(200);

        $this->assertTrue($this->user->fresh()->two_factor_confirmed);
    }

    /** @test */
    public function it_fails_with_invalid_code()
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $this->user->enableTwoFactor($secret);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/auth/2fa/verify', [
                'code' => '000000',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_disables_two_factor_with_password()
    {
        $this->user->update([
            'password' => bcrypt('password123'),
        ]);

        // تفعيل 2FA أولاً
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $this->user->enableTwoFactor($secret);
        $this->user->confirmTwoFactor();

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/auth/2fa/disable', [
                'password' => 'password123',
                'code'     => $google2fa->getCurrentOtp($secret),
            ]);

        $response->assertStatus(200);

        $this->assertFalse($this->user->fresh()->hasTwoFactorEnabled());
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/v1/auth/2fa/enable');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_code_is_6_digits()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/auth/2fa/verify', ['code' => '123']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }
}
```

## Pest

```php
<?php
// tests/Feature/TwoFactorPestTest.php

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Tymon\JWTAuth\Facades\JWTAuth;

it('returns qr code on enable', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $response = $this->withToken($token)->postJson('/api/v1/auth/2fa/enable');
    $response->assertStatus(200)->assertJsonStructure(['data' => ['qr_code', 'secret']]);
});

it('verifies with valid totp code', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();
    $user->enableTwoFactor($secret);

    $this->withToken($token)->postJson('/api/v1/auth/2fa/verify', [
        'code' => $google2fa->getCurrentOtp($secret),
    ])->assertStatus(200);

    expect($user->fresh()->two_factor_confirmed)->toBeTrue();
});
```
