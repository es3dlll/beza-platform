# 18 - الاختبارات (Testing)

## Feature Test — OtpTest

```php
<?php
// tests/Feature/OtpTest.php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'phone' => '0999123456',
        ]);
    }

    /** @test */
    public function it_requests_otp_successfully()
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '0999123456',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // تحقق من وجود OTP في Cache
        $this->assertNotNull(Cache::get('otp_0999123456'));
    }

    /** @test */
    public function it_verifies_otp_successfully()
    {
        // تخزين OTP في Cache (محاكاة)
        Cache::put('otp_0999123456', [
            'code'       => '123456',
            'expires_at' => now()->addMinutes(5)->timestamp,
            'attempts'   => 0,
        ], 300);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '0999123456',
            'otp'   => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // تحقق من تحديث phone_verified_at
        $this->assertNotNull($this->user->fresh()->phone_verified_at);
    }

    /** @test */
    public function it_fails_with_wrong_otp()
    {
        Cache::put('otp_0999123456', [
            'code'       => '123456',
            'expires_at' => now()->addMinutes(5)->timestamp,
            'attempts'   => 0,
        ], 300);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '0999123456',
            'otp'   => '999999',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_fails_with_expired_otp()
    {
        Cache::put('otp_0999123456', [
            'code'       => '123456',
            'expires_at' => now()->subMinute()->timestamp,
            'attempts'   => 0,
        ], 300);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '0999123456',
            'otp'   => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'انتهت صلاحية رمز التحقق']);
    }

    /** @test */
    public function it_fails_after_max_attempts()
    {
        Cache::put('otp_0999123456', [
            'code'       => '123456',
            'expires_at' => now()->addMinutes(5)->timestamp,
            'attempts'   => 4,
        ], 300);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '0999123456',
            'otp'   => '999999',
        ]);

        $response->assertStatus(422); // 4 → 5 fails, still under limit

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '0999123456',
            'otp'   => '999999',
        ]);

        $response->assertStatus(429); // exceeded
    }

    /** @test */
    public function it_fails_when_phone_not_registered()
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '0999000000',
        ]);

        $response->assertStatus(422);
    }
}
```

## Pest

```php
<?php
// tests/Feature/OtpPestTest.php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use function Pest\Laravel\postJson;

beforeEach(function () {
    User::factory()->create(['phone' => '0999123456']);
});

test('request otp stores code in cache', function () {
    postJson('/api/v1/auth/request-otp', ['phone' => '0999123456'])->assertStatus(200);
    expect(Cache::has('otp_0999123456'))->toBeTrue();
});

test('verify otp marks phone as verified', function () {
    Cache::put('otp_0999123456', ['code' => '123456', 'expires_at' => now()->addMinutes(5)->timestamp, 'attempts' => 0], 300);
    postJson('/api/v1/auth/verify-otp', ['phone' => '0999123456', 'otp' => '123456'])->assertStatus(200);
    expect(User::first()->phone_verified_at)->not->toBeNull();
});
```
