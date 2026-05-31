<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Ledger\Models\LedgerEntry;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_gets_wallet(): void
    {
        $response = $this->postJson('/v1/auth/register', [
            'name' => 'أحمد',
            'email' => 'ahmed@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message', 'data' => ['user', 'token', 'expires_in_minutes'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'ahmed@test.com']);
        $this->assertDatabaseHas('wallets', [
            'balance_fils' => 0,
            'currency' => 'SYP',
        ]);

        $user = User::where('email', 'ahmed@test.com')->first();
        $this->assertNotNull($user->wallet);
        $this->assertEquals(0, $user->wallet->balance_fils);
    }

    public function test_user_can_login_and_receive_token(): void
    {
        User::factory()->create([
            'email' => 'test@test.com',
            'password' => bcrypt('password123'),
        ]);

        Wallet::factory()->create([
            'user_id' => User::where('email', 'test@test.com')->first()->id,
        ]);

        $response = $this->postJson('/v1/auth/login', [
            'email' => 'test@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'تم تسجيل الدخول',
            ]);

        $this->assertArrayHasKey('token', $response->json('data'));
    }

    public function test_successful_transfer_between_wallets(): void
    {
        $sender = User::factory()->create();
        $senderWallet = Wallet::factory()->create([
            'user_id' => $sender->id,
            'balance_fils' => 5000,
        ]);

        $receiver = User::factory()->create();
        $receiverWallet = Wallet::factory()->create([
            'user_id' => $receiver->id,
            'balance_fils' => 0,
        ]);

        $engine = app(\App\Modules\Ledger\Services\CoreFinancialEngine::class);
        $money = \App\Modules\Core\ValueObjects\Money::fromFils(2000);
        $entry = $engine->transfer($money, $senderWallet, $receiverWallet, 'تحويل تجريبي');

        $this->assertNotNull($entry);
        $this->assertEquals(2000, $entry->amount_fils);

        $senderWallet->refresh();
        $receiverWallet->refresh();

        $this->assertEquals(3000, $senderWallet->balance_fils);
        $this->assertEquals(2000, $receiverWallet->balance_fils);
    }

    public function test_transfer_fails_with_insufficient_balance(): void
    {
        $sender = User::factory()->create();
        $senderWallet = Wallet::factory()->create([
            'user_id' => $sender->id,
            'balance_fils' => 500,
        ]);

        $receiver = User::factory()->create();
        $receiverWallet = Wallet::factory()->create([
            'user_id' => $receiver->id,
            'balance_fils' => 0,
        ]);

        $engine = app(\App\Modules\Ledger\Services\CoreFinancialEngine::class);
        $money = \App\Modules\Core\ValueObjects\Money::fromFils(2000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('الرصيد غير كافٍ');

        $engine->transfer($money, $senderWallet, $receiverWallet, 'محاولة فاشلة');
    }

    public function test_ledger_has_double_entry_after_successful_transfer(): void
    {
        $sender = User::factory()->create();
        $senderWallet = Wallet::factory()->create([
            'user_id' => $sender->id,
            'balance_fils' => 10000,
        ]);

        $receiver = User::factory()->create();
        $receiverWallet = Wallet::factory()->create([
            'user_id' => $receiver->id,
            'balance_fils' => 0,
        ]);

        $engine = app(\App\Modules\Ledger\Services\CoreFinancialEngine::class);
        $money = \App\Modules\Core\ValueObjects\Money::fromFils(3000);
        $engine->transfer($money, $senderWallet, $receiverWallet, 'تحويل', 'test', 'ref-001');

        $entries = LedgerEntry::where('amount_fils', 3000)->get();
        $this->assertCount(1, $entries);

        $entry = $entries->first();
        $this->assertEquals($senderWallet->id, $entry->debit_wallet_id);
        $this->assertEquals($receiverWallet->id, $entry->credit_wallet_id);
        $this->assertEquals('ref-001', $entry->reference_id);

        $metadata = $entry->metadata;
        $this->assertEquals(10000, $metadata['from_balance_before']);
        $this->assertEquals(7000, $metadata['from_balance_after']);
        $this->assertEquals(0, $metadata['to_balance_before']);
        $this->assertEquals(3000, $metadata['to_balance_after']);
    }
}
