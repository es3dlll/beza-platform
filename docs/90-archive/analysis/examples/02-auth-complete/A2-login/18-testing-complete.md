# 18 - الاختبارات (Testing)

## Feature Test — LoginTest

```php
<?php
// tests/Feature/LoginTest.php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'phone'    => '0999123456',
            'password' => Hash::make('password123'),
            'status'   => 'active',
        ]);
    }

    /** @test */
    public function it_logs_in_successfully()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone'    => '0999123456',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
            ]);

        $this->assertArrayHasKey('token', $response['data']);
    }

    /** @test */
    public function it_fails_with_wrong_password()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone'    => '0999123456',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'بيانات الدخول غير صحيحة']);
    }

    /** @test */
    public function it_fails_with_nonexistent_phone()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone'    => '0999000000',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_fails_for_suspended_account()
    {
        $this->user->update(['status' => 'suspended']);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone'    => '0999123456',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_locks_account_after_5_failed_attempts()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'phone'    => '0999123456',
                'password' => 'wrong',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'phone'    => '0999123456',
            'password' => 'wrong',
        ]);

        $response->assertStatus(429)
            ->assertJsonStructure(['data' => ['locked_remaining_minutes']]);
    }

    /** @test */
    public function it_updates_last_login_metadata()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone'     => '0999123456',
            'password'  => 'password123',
            'device_id' => 'device-123',
        ]);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertNotNull($this->user->last_login_at);
        $this->assertEquals('device-123', $this->user->device_id);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'password']);
    }
}
```

## Pest Tests

```php
<?php
// tests/Feature/LoginPestTest.php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\postJson;

beforeEach(function () {
    User::factory()->create([
        'phone' => '0999123456',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);
});

test('successful login returns token', function () {
    postJson('/api/v1/auth/login', [
        'phone' => '0999123456', 'password' => 'password123',
    ])->assertStatus(200)->assertJsonPath('data.token', fn($v) => $v !== null);
});

test('wrong credentials return 401', function () {
    postJson('/api/v1/auth/login', [
        'phone' => '0999123456', 'password' => 'wrong',
    ])->assertStatus(401);
});

test('account locks after 5 failures', function () {
    foreach (range(1, 5) as $i) {
        postJson('/api/v1/auth/login', ['phone' => '0999123456', 'password' => 'wrong']);
    }
    postJson('/api/v1/auth/login', ['phone' => '0999123456', 'password' => 'wrong'])
        ->assertStatus(429);
});
```
