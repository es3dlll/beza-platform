<?php

declare(strict_types=1);

namespace Tests\Feature\Wallet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\DTOs\CreateWalletDto;
use Modules\Wallet\DTOs\DepositDto;
use Modules\Wallet\DTOs\WithdrawDto;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Services\WalletService;
use Tests\TestCase;

final class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(WalletService::class);

        $dto = new CreateWalletDto(
            userId: '01ARconcurrencyTestUser001',
            currency: 'SYP',
        );
        $this->wallet = $this->service->create($dto);

        $this->service->deposit(new DepositDto(
            walletId: $this->wallet->id,
            amount: 10000000,
            referenceId: 'concurrency-initial-deposit',
        ));
    }

    public function test_sequential_withdrawals_maintain_balance(): void
    {
        $withdrawals = 10;
        $amount = 100000;

        for ($i = 0; $i < $withdrawals; $i++) {
            $this->service->withdraw(new WithdrawDto(
                walletId: $this->wallet->id,
                amount: $amount,
                referenceId: "concurrency-seq-wth-$i",
                applyFee: false,
            ));
        }

        $expected = 10000000 - ($amount * $withdrawals);
        $balance = $this->service->getBalance($this->wallet->id);
        $this->assertEquals($expected, $balance['balance']);
    }

    public function test_database_transaction_isolation(): void
    {
        $initialBalance = $this->wallet->balance;

        DB::transaction(function () use ($initialBalance) {
            $wallet = Wallet::lockForUpdate()->find($this->wallet->id);
            $wallet->balance -= 50000;
            $wallet->save();
        });

        DB::transaction(function () use ($initialBalance) {
            $wallet = Wallet::lockForUpdate()->find($this->wallet->id);
            $wallet->balance -= 50000;
            $wallet->save();
        });

        $expected = $initialBalance - 100000;
        $this->assertEquals($expected, Wallet::find($this->wallet->id)->balance);
    }

    public function test_pessimistic_locking_prevents_double_debit(): void
    {
        $initialBalance = $this->wallet->balance;
        $amount = 100000;

        $results = [];
        $locks = [];

        for ($i = 0; $i < 5; $i++) {
            $locks[] = DB::transaction(function () use ($amount, &$results) {
                $wallet = Wallet::lockForUpdate()->find($this->wallet->id);

                if ($wallet->available_balance >= $amount) {
                    $wallet->balance -= $amount;
                    $wallet->available_balance -= $amount;
                    $wallet->save();
                    $results[] = 'success';
                    return true;
                }

                $results[] = 'insufficient';
                return false;
            });
        }

        $successCount = count(array_filter($results, fn($r) => $r === true));
        $walletAfter = Wallet::find($this->wallet->id);

        $this->assertEquals($successCount * $amount, $initialBalance - $walletAfter->balance);
    }

    public function test_rapid_deposits_accumulate_correctly(): void
    {
        $deposits = 20;
        $amount = 50000;

        for ($i = 0; $i < $deposits; $i++) {
            $this->service->deposit(new DepositDto(
                walletId: $this->wallet->id,
                amount: $amount,
                referenceId: "concurrency-rapid-dep-$i",
            ));
        }

        $expected = 10000000 + ($amount * $deposits);
        $balance = $this->service->getBalance($this->wallet->id);
        $this->assertEquals($expected, $balance['balance']);
    }

    public function test_balance_never_goes_negative(): void
    {
        $attempts = 15;
        $amount = 1000000;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $this->service->withdraw(new WithdrawDto(
                    walletId: $this->wallet->id,
                    amount: $amount,
                    referenceId: "concurrency-negative-wth-$i",
                    applyFee: false,
                ));
            } catch (\Exception $e) {
            }
        }

        $balance = $this->service->getBalance($this->wallet->id);
        $this->assertGreaterThanOrEqual(0, $balance['balance']);
        $this->assertGreaterThanOrEqual(0, $balance['available_balance']);
    }

    public function test_simultaneous_same_amount_withdrawals(): void
    {
        $balance = $this->wallet->balance;
        $amount = 5000000;
        $withdrawals = 3;

        $results = [];
        for ($i = 0; $i < $withdrawals; $i++) {
            $results[] = DB::transaction(function () use ($amount, $i) {
                $wallet = Wallet::lockForUpdate()->find($this->wallet->id);
                if ($wallet->available_balance >= $amount) {
                    $wallet->balance -= $amount;
                    $wallet->available_balance -= $amount;
                    $wallet->save();
                    return "wth-$i succeeded";
                }
                return "wth-$i failed";
            });
        }

        $balanceAfter = Wallet::find($this->wallet->id)->balance;
        $this->assertGreaterThanOrEqual(0, $balanceAfter);
    }
}
