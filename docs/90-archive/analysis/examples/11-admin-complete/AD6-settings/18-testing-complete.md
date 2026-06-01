# 18 - كل الاختبارات (Testing)

## SettingsTest

```php
<?php
// tests/Feature/Admin/SettingsTest.php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->token = JWTAuth::fromUser($this->admin);

        // إدخال الإعدادات الافتراضية
        Setting::factory()->create(['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean']);
        Setting::factory()->create(['key' => 'fee_transfer', 'value' => '0', 'type' => 'number']);
        Setting::factory()->create(['key' => 'max_transfer_usd', 'value' => '2000', 'type' => 'number']);
        Setting::factory()->create(['key' => 'exchange_rate', 'value' => '13000', 'type' => 'number']);
    }

    /** @test */
    public function admin_can_view_settings()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'data' => ['general', 'fees', 'limits', 'exchange'],
            ]);
    }

    /** @test */
    public function admin_can_update_fees()
    {
        $response = $this->withToken($this->token)
            ->putJson('/api/v1/admin/settings/fees', [
                'transfer' => 1.5,
                'exchange' => 0.75,
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'تم تحديث رسوم المعاملات']);

        $this->assertEquals('1.5', Setting::getValue('fee_transfer'));
        $this->assertEquals('0.75', Setting::getValue('fee_exchange'));
    }

    /** @test */
    public function admin_can_update_limits()
    {
        $response = $this->withToken($this->token)
            ->putJson('/api/v1/admin/settings/limits', [
                'daily_transfer_usd' => 5000,
            ]);

        $response->assertStatus(200);
        $this->assertEquals('5000', Setting::getValue('max_transfer_usd'));
    }

    /** @test */
    public function admin_can_update_exchange_rate()
    {
        $response = $this->withToken($this->token)
            ->putJson('/api/v1/admin/settings/exchange-rate', [
                'rate' => 13500,
                'margin' => 1.0,
            ]);

        $response->assertStatus(200);
        $this->assertEquals('13500', Setting::getValue('exchange_rate'));
        $this->assertEquals('1', Setting::getValue('exchange_margin'));
    }

    /** @test */
    public function admin_can_toggle_maintenance_mode()
    {
        $response = $this->withToken($this->token)
            ->putJson('/api/v1/admin/settings', [
                'maintenance_mode' => true,
            ]);

        $response->assertStatus(200);
        $this->assertTrue((bool) Setting::getValue('maintenance_mode'));
    }

    /** @test */
    public function non_admin_gets_403()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $userToken = JWTAuth::fromUser($user);

        $response = $this->withToken($userToken)
            ->getJson('/api/v1/admin/settings');

        $response->assertStatus(403);
    }

    /** @test */
    public function exchange_rate_requires_valid_data()
    {
        $response = $this->withToken($this->token)
            ->putJson('/api/v1/admin/settings/exchange-rate', [
                'rate' => -1,
                'margin' => -5,
            ]);

        $response->assertStatus(422);
    }
}
```
