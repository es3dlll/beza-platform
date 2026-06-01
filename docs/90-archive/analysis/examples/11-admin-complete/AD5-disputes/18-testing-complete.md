# 18 - كل الاختبارات (Testing)

## DisputeTest

```php
<?php
// tests/Feature/Admin/DisputeTest.php

namespace Tests\Feature\Admin;

use App\Models\Dispute;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DisputeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $complainant;
    private User $respondent;
    private Transaction $transaction;
    private string $adminToken;
    private string $userToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->complainant = User::factory()->create();
        $this->respondent = User::factory()->create();

        $fromWallet = Wallet::factory()->create([
            'user_id' => $this->respondent->id,
            'currency' => 'USD', 'balance' => 1000,
        ]);
        $toWallet = Wallet::factory()->create([
            'user_id' => $this->complainant->id,
            'currency' => 'USD', 'balance' => 0,
        ]);

        $this->transaction = Transaction::factory()->create([
            'from_wallet_id' => $fromWallet->id,
            'to_wallet_id' => $toWallet->id,
            'amount' => 100, 'status' => 'completed',
        ]);

        $this->adminToken = JWTAuth::fromUser($this->admin);
        $this->userToken = JWTAuth::fromUser($this->complainant);
    }

    /** @test */
    public function user_can_submit_dispute()
    {
        $response = $this->withToken($this->userToken)
            ->postJson('/api/v1/support/disputes', [
                'transaction_id' => $this->transaction->id,
                'reason' => 'منتج لم يصل',
                'description' => 'لقد طلبت منتجاً منذ أسبوع ولم يصلني حتى الآن',
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'تم تقديم النزاع بنجاح']);
    }

    /** @test */
    public function user_can_submit_dispute_with_evidence()
    {
        $file = UploadedFile::fake()->image('evidence.jpg');

        $response = $this->withToken($this->userToken)
            ->post('/api/v1/support/disputes', [
                'transaction_id' => $this->transaction->id,
                'reason' => 'منتج لم يصل',
                'description' => 'لم يصلني المنتج بعد أسبوع من الشراء',
                'evidence_files' => [$file],
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function admin_can_view_disputes()
    {
        Dispute::factory()->create([
            'transaction_id' => $this->transaction->id,
            'complainant_id' => $this->complainant->id,
            'respondent_id' => $this->respondent->id,
        ]);

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/v1/admin/disputes');

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);
    }

    /** @test */
    public function admin_can_resolve_dispute_with_refund()
    {
        $dispute = Dispute::factory()->create([
            'transaction_id' => $this->transaction->id,
            'complainant_id' => $this->complainant->id,
            'respondent_id' => $this->respondent->id,
            'status' => 'open',
        ]);

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/v1/admin/disputes/{$dispute->id}/resolve", [
                'resolution' => 'refund',
                'admin_notes' => 'تم التحقق، استرجاع المبلغ',
            ]);

        $response->assertStatus(200);

        // التحقق من تحديث الرصيد
        $this->assertEquals(900, $this->respondent->wallets()->where('currency', 'USD')->first()->balance);
        $this->assertEquals(100, $this->complainant->wallets()->where('currency', 'USD')->first()->balance);
    }

    /** @test */
    public function admin_can_reject_dispute()
    {
        $dispute = Dispute::factory()->create([
            'transaction_id' => $this->transaction->id,
            'complainant_id' => $this->complainant->id,
            'respondent_id' => $this->respondent->id,
            'status' => 'open',
        ]);

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/v1/admin/disputes/{$dispute->id}/resolve", [
                'resolution' => 'reject',
                'admin_notes' => 'الأدلة غير كافية',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('rejected', $dispute->fresh()->status);
    }

    /** @test */
    public function cannot_resolve_already_resolved_dispute()
    {
        $dispute = Dispute::factory()->create([
            'transaction_id' => $this->transaction->id,
            'complainant_id' => $this->complainant->id,
            'respondent_id' => $this->respondent->id,
            'status' => 'resolved',
        ]);

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/v1/admin/disputes/{$dispute->id}/resolve", [
                'resolution' => 'refund',
            ]);

        $response->assertStatus(422);
    }
}
```
