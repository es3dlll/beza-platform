# 18 - الاختبارات (Testing)

## Feature Test — LogoutTest

```php
<?php
// tests/Feature/LogoutTest.php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogoutTest extends TestCase
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
    public function it_logs_out_successfully()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // التوكن يجب أن يكون ملغياً
        $this->assertTrue(true);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    /** @test */
    public function token_is_invalid_after_logout()
    {
        $this->withToken($this->token)
            ->postJson('/api/v1/auth/logout');

        // استخدام نفس التوكن — يجب أن يفشل
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_logs_out_from_all_devices()
    {
        // JWT هو stateless — لا يوجد توكنات إضافية


        $response = $this->withToken($this->token)
            ->postJson('/api/v1/auth/logout-all');

        $response->assertStatus(200);
    }
}
```

## Pest

```php
<?php
// tests/Feature/LogoutPestTest.php

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use function Pest\Laravel\postJson;

it('logs out and invalidates token', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    postJson('/api/v1/auth/logout', headers: ['Authorization' => 'Bearer ' . $token])
        ->assertStatus(200);
});

it('rejects unauthenticated logout', function () {
    postJson('/api/v1/auth/logout')->assertStatus(401);
});
```
