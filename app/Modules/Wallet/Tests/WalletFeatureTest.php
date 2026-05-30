<?php

declare(strict_types=1);

namespace Modules\Wallet\Tests;

use Modules\Wallet\DTOs\CreateWalletDto;
use Modules\Wallet\DTOs\DepositDto;
use Modules\Wallet\DTOs\WithdrawDto;
use Modules\Wallet\DTOs\TransferDto;
use Modules\Wallet\Exceptions\InsufficientBalanceException;
use Modules\Wallet\Exceptions\WalletNotFoundException;
use Modules\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Modules\Wallet\Services\WalletService;
use Tests\TestCase;

final class WalletFeatureTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(WalletService::class);
    }

    private function createUser(string $id): User
    {
        return User::factory()->create([
            'id' => $id,
            'phone' => '+963' . substr($id, -9),
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);
    }

    private function ensureUser(string $userId): void
    {
        if (!User::find($userId)) {
            $this->createUser($userId);
        }
    }

    public function test_can_create_wallet(): void
    {
        $this->ensureUser('01AR12345678901234567890');
        $dto = new CreateWalletDto(
            userId: '01AR12345678901234567890',
            currency: 'SYP',
        );

        $wallet = $this->service->create($dto);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals('SYP', $wallet->currency);
        $this->assertEquals(0, $wallet->balance);
        $this->assertEquals('active', $wallet->status);
    }

    public function test_returns_existing_wallet_on_duplicate(): void
    {
        $this->ensureUser('01AR12345678901234567891');
        $dto = new CreateWalletDto(userId: '01AR12345678901234567891', currency: 'SYP');

        $first = $this->service->create($dto);
        $second = $this->service->create($dto);

        $this->assertEquals($first->id, $second->id);
    }

    public function test_throws_exception_for_missing_wallet(): void
    {
        $this->expectException(WalletNotFoundException::class);
        $this->service->getBalance('nonexistent');
    }

    public function test_deposit_updates_balance(): void
    {
        $this->ensureUser('01AR12345678901234567892');
        $dto = new CreateWalletDto(userId: '01AR12345678901234567892', currency: 'SYP');
        $wallet = $this->service->create($dto);

        $deposit = new DepositDto(
            walletId: $wallet->id,
            amount: 50000,
            referenceId: 'dep-test-001',
        );

        $result = $this->service->deposit($deposit);

        $this->assertEquals(50000, $result->balance);
        $this->assertEquals(50000, $result->available_balance);
    }

    public function test_multiple_deposits_accumulate(): void
    {
        $this->ensureUser('01AR12345678901234567893');
        $dto = new CreateWalletDto(userId: '01AR12345678901234567893', currency: 'SYP');
        $wallet = $this->service->create($dto);

        $this->service->deposit(new DepositDto($wallet->id, 10000, referenceId: 'dep-multi-1'));
        $this->service->deposit(new DepositDto($wallet->id, 20000, referenceId: 'dep-multi-2'));
        $this->service->deposit(new DepositDto($wallet->id, 30000, referenceId: 'dep-multi-3'));

        $balance = $this->service->getBalance($wallet->id);
        $this->assertEquals(60000, $balance['balance']);
    }

    public function test_withdrawal_reduces_balance(): void
    {
        $this->ensureUser('01AR12345678901234567894');
        $dto = new CreateWalletDto(userId: '01AR12345678901234567894', currency: 'SYP');
        $wallet = $this->service->create($dto);

        $this->service->deposit(new DepositDto($wallet->id, 100000, referenceId: 'dep-wth-1'));

        $withdraw = new WithdrawDto(
            walletId: $wallet->id,
            amount: 30000,
            referenceId: 'wth-test-001',
            applyFee: false,
        );

        $result = $this->service->withdraw($withdraw);

        $this->assertEquals(70000, $result->balance);
    }

    public function test_withdrawal_fails_on_insufficient_balance(): void
    {
        $this->ensureUser('01AR12345678901234567895');
        $dto = new CreateWalletDto(userId: '01AR12345678901234567895', currency: 'SYP');
        $wallet = $this->service->create($dto);

        $this->service->deposit(new DepositDto($wallet->id, 10000, referenceId: 'dep-ins-1'));

        $this->expectException(InsufficientBalanceException::class);

        $withdraw = new WithdrawDto(
            walletId: $wallet->id,
            amount: 50000,
            referenceId: 'wth-ins-1',
            applyFee: false,
        );
        $this->service->withdraw($withdraw);
    }

    public function test_can_transfer_between_wallets(): void
    {
        $userA = '01AR12345678901234567896';
        $userB = '01AR12345678901234567897';
        $this->ensureUser($userA);
        $this->ensureUser($userB);

        $walletA = $this->service->create(new CreateWalletDto($userA, 'SYP'));
        $walletB = $this->service->create(new CreateWalletDto($userB, 'SYP'));

        $this->service->deposit(new DepositDto($walletA->id, 100000, referenceId: 'dep-trf-1'));

        $transfer = new TransferDto(
            fromWalletId: $walletA->id,
            toWalletId: $walletB->id,
            amount: 40000,
            referenceId: 'trf-test-001',
            applyFee: false,
        );

        $result = $this->service->transfer($transfer);

        $this->assertEquals(60000, $result['from']->balance);
        $this->assertEquals(40000, $result['to']->balance);
    }

    public function test_transfer_fails_on_insufficient_balance(): void
    {
        $userA = '01AR12345678901234567898';
        $userB = '01AR12345678901234567899';
        $this->ensureUser($userA);
        $this->ensureUser($userB);

        $walletA = $this->service->create(new CreateWalletDto($userA, 'SYP'));
        $walletB = $this->service->create(new CreateWalletDto($userB, 'SYP'));

        $this->expectException(InsufficientBalanceException::class);

        $transfer = new TransferDto(
            fromWalletId: $walletA->id,
            toWalletId: $walletB->id,
            amount: 50000,
            referenceId: 'trf-ins-1',
            applyFee: false,
        );
        $this->service->transfer($transfer);
    }

    public function test_can_get_transaction_history(): void
    {
        $this->ensureUser('01AR12345678901234567800');
        $dto = new CreateWalletDto(userId: '01AR12345678901234567800', currency: 'SYP');
        $wallet = $this->service->create($dto);

        $this->service->deposit(new DepositDto($wallet->id, 50000, referenceId: 'dep-hist-1'));
        $this->service->deposit(new DepositDto($wallet->id, 30000, referenceId: 'dep-hist-2'));

        $txns = $this->service->getTransactions($wallet->id);

        $this->assertCount(2, $txns);
    }

    public function test_wallet_balance_includes_all_operations(): void
    {
        $this->ensureUser('01AR12345678901234567801');
        $dto = new CreateWalletDto(userId: '01AR12345678901234567801', currency: 'SYP');
        $wallet = $this->service->create($dto);

        $this->service->deposit(new DepositDto($wallet->id, 200000, referenceId: 'dep-full-1'));
        $this->service->withdraw(new WithdrawDto($wallet->id, 50000, referenceId: 'wth-full-1', applyFee: false));

        $balance = $this->service->getBalance($wallet->id);
        $this->assertEquals(150000, $balance['balance']);
    }

    public function test_can_create_usd_wallet(): void
    {
        $this->ensureUser('01AR12345678901234567802');
        $dto = new CreateWalletDto(
            userId: '01AR12345678901234567802',
            currency: 'USD',
        );

        $wallet = $this->service->create($dto);

        $this->assertEquals('USD', $wallet->currency);
        $this->assertEquals(0, $wallet->balance);
    }

    public function test_user_can_have_multi_currency_wallets(): void
    {
        $userId = '01AR12345678901234567803';
        $this->ensureUser($userId);

        $syp = $this->service->create(new CreateWalletDto($userId, 'SYP'));
        $usd = $this->service->create(new CreateWalletDto($userId, 'USD'));

        $this->assertNotEquals($syp->id, $usd->id);
        $this->assertEquals('SYP', $syp->currency);
        $this->assertEquals('USD', $usd->currency);
    }
}
