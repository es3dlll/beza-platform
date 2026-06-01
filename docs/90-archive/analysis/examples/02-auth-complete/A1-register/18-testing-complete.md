# 18 - الاختبارات (Testing)

## Feature Test — RegisterTest

```php
<?php
// tests/Feature/RegisterTest.php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_registers_a_new_user_successfully()
    {
        Event::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'علي أحمد',
            'phone'                 => '0999123456',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'pin_code'              => '1234',
            'pin_code_confirmation' => '1234',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تم التسجيل بنجاح',
            ]);

        // تحقق من إنشاء المستخدم
        $this->assertDatabaseHas('users', [
            'phone' => '0999123456',
            'name'  => 'علي أحمد',
            'status' => 'pending',
        ]);

        // تحقق من وجود محفظتين
        $user = User::where('phone', '0999123456')->first();
        $this->assertCount(2, $user->wallets);
        $this->assertEquals(5.00, $user->usdWallet->balance);
        $this->assertEquals(0.00, $user->sypWallet->balance);

        // تحقق من Hash
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertTrue(Hash::check('1234', $user->pin_code));

        // تحقق من Event
        Event::assertDispatched(UserRegistered::class);

        // تحقق من وجود token
        $this->assertArrayHasKey('token', $response['data']);
    }

    /** @test */
    public function it_fails_when_phone_already_exists()
    {
        User::factory()->create(['phone' => '0999123456']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'مستخدم آخر',
            'phone'                 => '0999123456',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'pin_code'              => '1234',
            'pin_code_confirmation' => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'password', 'pin_code']);
    }

    /** @test */
    public function it_validates_phone_format()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'علي',
            'phone'                 => '12345',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'pin_code'              => '1234',
            'pin_code_confirmation' => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function it_validates_password_min_length()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'علي',
            'phone'                 => '0999123456',
            'password'              => 'short',
            'password_confirmation' => 'short',
            'pin_code'              => '1234',
            'pin_code_confirmation' => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_validates_pin_must_be_4_digits()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'علي',
            'phone'                 => '0999123456',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'pin_code'              => '123',
            'pin_code_confirmation' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pin_code']);
    }
}
```

## Pest Tests

```php
<?php
// tests/Feature/RegisterPestTest.php

use App\Models\User;
use function Pest\Laravel\postJson;

test('successful registration creates user and wallets', function () {
    $response = postJson('/api/v1/auth/register', [
        'name' => 'علي', 'phone' => '0999123456',
        'password' => 'password123', 'password_confirmation' => 'password123',
        'pin_code' => '1234', 'pin_code_confirmation' => '1234',
    ]);

    $response->assertStatus(201);
    expect(User::count())->toBe(1);
    expect(User::first()->wallets)->toHaveCount(2);
});

test('duplicate phone is rejected', function () {
    User::factory()->create(['phone' => '0999123456']);
    postJson('/api/v1/auth/register', [
        'name' => 'علي', 'phone' => '0999123456',
        'password' => 'password123', 'password_confirmation' => 'password123',
        'pin_code' => '1234', 'pin_code_confirmation' => '1234',
    ])->assertStatus(422);
});
```
