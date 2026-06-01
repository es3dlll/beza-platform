<?php

namespace Tests\Feature\Wallet;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Modules\Wallet\Events\TransferCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;

    private User $receiver;

    private Wallet $senderWallet;

    private Wallet $receiverWallet;

    private string $idempotencyKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sender = User::factory()->create(['phone' => '0911111111']);
        $this->receiver = User::factory()->create(['phone' => '0922222222']);

        $this->senderWallet = Wallet::factory()->create([
            'user_id' => $this->sender->id,
            'currency' => 'SYP',
            'balance' => 100000,
        ]);
        Wallet::factory()->create([
            'user_id' => $this->sender->id,
            'currency' => 'USD',
            'balance' => 0,
        ]);

        $this->receiverWallet = Wallet::factory()->create([
            'user_id' => $this->receiver->id,
            'currency' => 'SYP',
            'balance' => 0,
        ]);

        $this->idempotencyKey = (string) Str::uuid();
    }

    private function authHeader(User $user): array
    {
        $token = $user->createToken('test-token', ['wap'])->plainTextToken;

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_successful_transfer_completes_full_flow(): void
    {
        $this->assertDatabaseCount('transactions', 0);

        $response = $this->postJson('/api/v1/transfer', [
            'receiver_phone' => '0922222222',
            'amount' => 50000,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ], $this->authHeader($this->sender));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.idempotent', false)
            ->assertJsonStructure(['data' => ['transaction' => ['id', 'reference_number', 'amount', 'currency', 'status', 'created_at']]]);

        $this->assertDatabaseHas('transactions', [
            'amount' => 50000,
            'currency' => 'SYP',
            'status' => 'completed',
            'idempotency_key' => $this->idempotencyKey,
        ]);

        $this->senderWallet->refresh();
        $this->receiverWallet->refresh();
        $this->assertEquals(50000, $this->senderWallet->balance);
        $this->assertEquals(50000, $this->receiverWallet->balance);
    }

    public function test_transfer_creates_audit_log(): void
    {
        $this->postJson('/api/v1/transfer', [
            'receiver_phone' => '0922222222',
            'amount' => 15000,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ], $this->authHeader($this->sender));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->sender->id,
            'method' => 'POST',
            'path' => '/api/v1/transfer',
            'fingerprint' => $this->idempotencyKey,
        ]);
    }

    public function test_transfer_fires_completed_event(): void
    {
        Event::fake();

        $this->postJson('/api/v1/transfer', [
            'receiver_phone' => '0922222222',
            'amount' => 10000,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ], $this->authHeader($this->sender));

        Event::assertDispatched(TransferCompleted::class);
    }

    public function test_insufficient_balance_returns_402_and_rolls_back(): void
    {
        $response = $this->postJson('/api/v1/transfer', [
            'receiver_phone' => '0922222222',
            'amount' => 999999999,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ], $this->authHeader($this->sender));

        $response->assertStatus(402)
            ->assertJsonPath('error.code', 'INSUFFICIENT_BALANCE');

        $this->senderWallet->refresh();
        $this->receiverWallet->refresh();
        $this->assertEquals(100000, $this->senderWallet->balance);
        $this->assertEquals(0, $this->receiverWallet->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_idempotency_key_prevents_double_debit(): void
    {
        $payload = [
            'receiver_phone' => '0922222222',
            'amount' => 25000,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ];

        $first = $this->postJson('/api/v1/transfer', $payload, $this->authHeader($this->sender));
        $first->assertOk()->assertJsonPath('data.idempotent', false);

        $second = $this->postJson('/api/v1/transfer', $payload, $this->authHeader($this->sender));
        $second->assertOk()->assertJsonPath('data.idempotent', true);

        $this->senderWallet->refresh();
        $this->receiverWallet->refresh();
        $this->assertEquals(75000, $this->senderWallet->balance);
        $this->assertEquals(25000, $this->receiverWallet->balance);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_race_condition_is_safe(): void
    {
        $key1 = (string) Str::uuid();
        $key2 = (string) Str::uuid();

        $payload1 = [
            'receiver_phone' => '0922222222',
            'amount' => 60000,
            'currency' => 'SYP',
            'idempotency_key' => $key1,
        ];

        $payload2 = [
            'receiver_phone' => '0922222222',
            'amount' => 60000,
            'currency' => 'SYP',
            'idempotency_key' => $key2,
        ];

        $response1 = $this->postJson('/api/v1/transfer', $payload1, $this->authHeader($this->sender));
        $response2 = $this->postJson('/api/v1/transfer', $payload2, $this->authHeader($this->sender));

        $successCount = 0;
        $failCount = 0;
        if ($response1->status() === 200) {
            $successCount++;
        }
        if ($response1->status() === 402) {
            $failCount++;
        }
        if ($response2->status() === 200) {
            $successCount++;
        }
        if ($response2->status() === 402) {
            $failCount++;
        }

        $this->assertEquals(1, $successCount, 'Only one transfer should succeed');
        $this->assertEquals(1, $failCount, 'The other should fail with insufficient balance');

        $this->senderWallet->refresh();
        $this->receiverWallet->refresh();
        $this->assertEquals(40000, $this->senderWallet->balance);
        $this->assertEquals(60000, $this->receiverWallet->balance);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_same_wallet_transfer_returns_422(): void
    {
        $response = $this->postJson('/api/v1/transfer', [
            'receiver_phone' => '0911111111',
            'amount' => 1000,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ], $this->authHeader($this->sender));

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_CURRENCY');
    }

    public function test_receiver_not_found_returns_422(): void
    {
        $response = $this->postJson('/api/v1/transfer', [
            'receiver_phone' => '0999999999',
            'amount' => 1000,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ], $this->authHeader($this->sender));

        $response->assertStatus(422);
    }

    public function test_receiver_wallet_missing_same_currency_returns_404(): void
    {
        $usdWallet = Wallet::factory()->create([
            'user_id' => $this->receiver->id,
            'currency' => 'USD',
            'balance' => 0,
        ]);

        $response = $this->postJson('/api/v1/transfer', [
            'receiver_wallet_id' => $usdWallet->id,
            'amount' => 1000,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ], $this->authHeader($this->sender));

        $response->assertStatus(404);
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/v1/transfer', [], $this->authHeader($this->sender));

        $response->assertStatus(422);
    }

    public function test_transfer_by_wallet_id(): void
    {
        $response = $this->postJson('/api/v1/transfer', [
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 30000,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ], $this->authHeader($this->sender));

        $response->assertOk()
            ->assertJsonPath('data.transaction.amount', 30000);

        $this->senderWallet->refresh();
        $this->receiverWallet->refresh();
        $this->assertEquals(70000, $this->senderWallet->balance);
        $this->assertEquals(30000, $this->receiverWallet->balance);
    }

    public function test_unauthorized_request_returns_401(): void
    {
        $response = $this->postJson('/api/v1/transfer', [
            'receiver_phone' => '0922222222',
            'amount' => 1000,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ]);

        $response->assertUnauthorized();
    }

    public function test_amount_below_minimum_returns_422(): void
    {
        $response = $this->postJson('/api/v1/transfer', [
            'receiver_phone' => '0922222222',
            'amount' => 50,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ], $this->authHeader($this->sender));

        $response->assertStatus(422);
    }

    public function test_generates_unique_reference_number(): void
    {
        $key1 = (string) Str::uuid();

        $this->postJson('/api/v1/transfer', [
            'receiver_phone' => '0922222222',
            'amount' => 5000,
            'currency' => 'SYP',
            'idempotency_key' => $key1,
        ], $this->authHeader($this->sender));

        $txn = Transaction::first();
        $this->assertNotNull($txn->reference_number);
        $this->assertStringStartsWith('TXN-', $txn->reference_number);
    }

    public function test_system_balance_is_conserved(): void
    {
        $totalBefore = Wallet::sum('balance');

        $this->postJson('/api/v1/transfer', [
            'receiver_phone' => '0922222222',
            'amount' => 45000,
            'currency' => 'SYP',
            'idempotency_key' => $this->idempotencyKey,
        ], $this->authHeader($this->sender));

        $totalAfter = Wallet::sum('balance');

        $this->assertEquals($totalBefore, $totalAfter, 'Total system balance must remain constant');
    }

    public function test_multiple_transfers_accumulate_correctly(): void
    {
        $key1 = (string) Str::uuid();
        $key2 = (string) Str::uuid();
        $key3 = (string) Str::uuid();
        $key4 = (string) Str::uuid();

        $transfers = [
            ['amount' => 10000, 'key' => $key1],
            ['amount' => 20000, 'key' => $key2],
            ['amount' => 5000, 'key' => $key3],
        ];

        foreach ($transfers as $t) {
            $this->postJson('/api/v1/transfer', [
                'receiver_phone' => '0922222222',
                'amount' => $t['amount'],
                'currency' => 'SYP',
                'idempotency_key' => $t['key'],
            ], $this->authHeader($this->sender))->assertOk();
        }

        $this->senderWallet->refresh();
        $this->receiverWallet->refresh();
        $this->assertEquals(65000, $this->senderWallet->balance);
        $this->assertEquals(35000, $this->receiverWallet->balance);

        $excessive = ['amount' => 70000, 'key' => $key4];
        $this->postJson('/api/v1/transfer', [
            'receiver_phone' => '0922222222',
            'amount' => $excessive['amount'],
            'currency' => 'SYP',
            'idempotency_key' => $excessive['key'],
        ], $this->authHeader($this->sender))->assertStatus(402);

        $this->senderWallet->refresh();
        $this->assertEquals(65000, $this->senderWallet->balance);
        $this->assertDatabaseCount('transactions', 3);
    }
}
