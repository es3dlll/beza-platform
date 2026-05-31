# 18 - كل الاختبارات (Testing)

## MerchantApprovalTest

```php
<?php
// tests/Feature/Admin/MerchantApprovalTest.php

namespace Tests\Feature\Admin;

use App\Models\Merchant;
use App\Models\MerchantDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MerchantApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $applicant;
    private Merchant $application;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->applicant = User::factory()->create([
            'kyc_status' => 'verified',
            'status'     => 'active',
        ]);

        $this->application = Merchant::factory()->create([
            'user_id' => $this->applicant->id,
            'status'  => 'pending',
        ]);

        MerchantDocument::factory()->count(3)->create([
            'merchant_id' => $this->application->id,
            'status'      => 'approved',
        ]);

        $this->token = JWTAuth::fromUser($this->admin);
    }

    /** @test */
    public function admin_can_view_pending_applications()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/merchants/applications');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_approve_merchant()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/v1/admin/merchants/{$this->application->id}/approve");

        $response->assertStatus(200)
            ->assertJson(['message' => 'تم الموافقة على طلب التاجر']);

        $this->assertEquals('active', $this->application->fresh()->status);
        $this->assertEquals(1, $this->applicant->fresh()->is_merchant);
    }

    /** @test */
    public function admin_can_reject_merchant_with_reason()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/v1/admin/merchants/{$this->application->id}/reject", [
                'reason' => 'المستندات المقدمة غير مكتملة. يرجى إرفاق السجل التجاري.',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('rejected', $this->application->fresh()->status);
        $this->assertNotNull($this->application->fresh()->rejection_reason);
    }

    /** @test */
    public function cannot_approve_already_processed_application()
    {
        $this->application->update(['status' => 'active']);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/admin/merchants/{$this->application->id}/approve");

        $response->assertStatus(422);
    }

    /** @test */
    public function cannot_approve_without_kyc()
    {
        $user = User::factory()->create(['kyc_status' => 'not_submitted']);
        $app = Merchant::factory()->create([
            'user_id' => $user->id,
            'status'  => 'pending',
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/admin/merchants/{$app->id}/approve");

        $response->assertStatus(422);
    }

    /** @test */
    public function non_admin_gets_403()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $userToken = JWTAuth::fromUser($user);

        $response = $this->withToken($userToken)
            ->getJson('/api/v1/admin/merchants/applications');

        $response->assertStatus(403);
    }

    /** @test */
    public function reject_requires_reason()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/v1/admin/merchants/{$this->application->id}/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }
}
```

## تشغيل الاختبارات

```bash
php artisan test --filter=MerchantApprovalTest
```
