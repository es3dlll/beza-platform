<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Identity\Exceptions\InsufficientBalanceException;
use App\Modules\Identity\Exceptions\WalletNotFoundException;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\Wallet;
use App\Modules\Identity\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class IdentityTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = $this->app->make(WalletService::class);
    }

    public function test_create_user(): void
    {
        $user = User::create([
            'id' => Str::ulid()->toBase32(),
            'phone' => '963955512345',
            'name' => 'Test User',
            'name_ar' => 'مستخدم اختباري',
            'email' => 'test@example.com',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'kyc_tier' => 't1',
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('963955512345', $user->phone);
        $this->assertEquals('active', $user->status);
        $this->assertEquals('t1', $user->kyc_tier);
        $this->assertTrue($user->isActive());
        $this->assertTrue($user->canTransact());
    }

    public function test_create_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user->id);

        $this->assertNotNull($wallet->id);
        $this->assertEquals($user->id, $wallet->user_id);
        $this->assertEquals('SYP', $wallet->currency);
        $this->assertEquals(0, $wallet->balance);
        $this->assertEquals('active', $wallet->status);
    }

    public function test_get_wallet_balance(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user->id);

        $balance = $this->walletService->getBalance($wallet->id);

        $this->assertEquals(0, $balance);
    }

    public function test_credit_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user->id);

        $this->walletService->credit($wallet->id, 500000);

        $this->assertEquals(500000, $wallet->fresh()->balance);
    }

    public function test_debit_wallet_sufficient(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user->id);
        $this->walletService->credit($wallet->id, 1000000);

        $this->walletService->debit($wallet->id, 300000);

        $this->assertEquals(700000, $wallet->fresh()->balance);
    }

    public function test_debit_wallet_insufficient(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user->id);

        $this->expectException(InsufficientBalanceException::class);

        $this->walletService->debit($wallet->id, 100);
    }

    public function test_user_wallet_relationships(): void
    {
        $user = User::factory()->create();
        $wallet1 = $this->walletService->createWallet($user->id, 'SYP');
        $wallet2 = $this->walletService->createWallet($user->id, 'USD');

        $this->assertCount(2, $user->wallets);
        $this->assertEquals($wallet1->id, $user->primaryWallet->id);
    }

    public function test_wallet_freeze(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user->id);

        $this->walletService->freeze($wallet->id);

        $this->assertEquals('frozen', $wallet->fresh()->status);
    }

    public function test_wallet_not_found_throws_exception(): void
    {
        $this->expectException(WalletNotFoundException::class);

        $this->walletService->getWallet('nonexistent');
    }

    public function test_get_tier_limit(): void
    {
        $user = User::factory()->create(['kyc_tier' => 't0']);
        $this->assertEquals(0, $user->getTierLimit());

        $user->update(['kyc_tier' => 't1']);
        $this->assertEquals(1000000, $user->fresh()->getTierLimit());

        $user->update(['kyc_tier' => 't2']);
        $this->assertEquals(10000000, $user->fresh()->getTierLimit());

        $user->update(['kyc_tier' => 't3']);
        $this->assertEquals(100000000, $user->fresh()->getTierLimit());
    }

    public function test_wallet_model_credit_and_debit(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 2000000]);

        $wallet->credit(500000);
        $this->assertEquals(2500000, $wallet->fresh()->balance);

        $wallet->debit(1000000);
        $this->assertEquals(1500000, $wallet->fresh()->balance);
    }

    public function test_wallet_close(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        $wallet->close();
        $this->assertEquals('closed', $wallet->fresh()->status);
    }

    public function test_wallet_is_active(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $this->assertTrue($wallet->isActive());

        $wallet->frozen();
        $this->assertFalse($wallet->fresh()->isActive());

        $wallet->close();
        $this->assertFalse($wallet->fresh()->isActive());
    }

    public function test_user_can_transact_when_suspended(): void
    {
        $user = User::factory()->create(['status' => 'suspended']);

        $this->assertFalse($user->isActive());
        $this->assertFalse($user->canTransact());
    }

    public function test_user_can_transact_when_locked(): void
    {
        $user = User::factory()->create(['status' => 'locked']);

        $this->assertFalse($user->isActive());
        $this->assertFalse($user->canTransact());
    }
}
