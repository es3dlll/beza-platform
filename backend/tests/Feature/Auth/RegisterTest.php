<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Core\Enums\WalletStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '0933123456',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email', 'phone'],
                    'token',
                    'wallets',
                ],
            ]);

        $this->assertTrue($response['success']);
        $this->assertEquals('أحمد محمد', $response['data']['user']['name']);
        $this->assertCount(2, $response['data']['wallets']);

        $currencies = collect($response['data']['wallets'])->pluck('currency')->toArray();
        $this->assertContains('SYP', $currencies);
        $this->assertContains('USD', $currencies);

        $this->assertDatabaseHas('users', [
            'email' => 'ahmed@example.com',
            'phone' => '0933123456',
        ]);

        $this->assertDatabaseHas('wallets', [
            'currency' => 'SYP',
        ]);

        $this->assertDatabaseHas('wallets', [
            'currency' => 'USD',
        ]);
    }

    public function test_registration_fails_with_existing_email(): void
    {
        User::factory()->create(['email' => 'ahmed@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '0933123456',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_with_existing_phone(): void
    {
        User::factory()->create(['phone' => '0933123456']);

        $response = $this->postJson('/api/register', [
            'name' => 'أحمد محمد',
            'email' => 'ahmed2@example.com',
            'phone' => '0933123456',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_registration_fails_with_weak_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '0933123456',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_fails_with_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '12345',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_registration_returns_valid_sanctum_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '0933123456',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertStatus(201);
        $token = $response['data']['token'];

        $this->assertNotEmpty($token);
        $this->assertStringContainsString('|', $token);

        $profileResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . explode('|', $token)[1],
        ])->getJson('/api/user');

        $profileResponse->assertStatus(200);
        $this->assertEquals('ahmed@example.com', $profileResponse['email']);
    }

    public function test_registration_sets_correct_wallet_defaults(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '0933123456',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertStatus(201);

        foreach ($response['data']['wallets'] as $wallet) {
            $this->assertEquals('0.0000', $wallet['balance']);
            $this->assertEquals(WalletStatus::Active->value, $wallet['status']);
        }
    }
}
