# 09 - اختبارات الخدمات (Service Tests)

## TransferServiceTest

```php
<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidPinException;
use App\Exceptions\SelfTransferException;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TransferService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransferService $service;
    private User $sender;
    private User $receiver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransferService(new WalletService());

        $this->sender = User::factory()->create([
            'pin_code' => Hash::make('1234'),
            'status' => 'active',
        ]);
        $this->receiver = User::factory()->create(['status' => 'active']);

        Wallet::factory()->create([
            'user_id' => $this->sender->id, 'currency' => 'USD', 'balance' => 500,
        ]);
        Wallet::factory()->create([
            'user_id' => $this->receiver->id, 'currency' => 'USD', 'balance' => 0,
        ]);
    }

    /** @test */
    public function it_transfers_money_successfully()
    {
        $result = $this->service->transfer(
            fromUser: $this->sender,
            toPhone: $this->receiver->phone,
            amount: 100,
            currency: 'USD',
            pin: '1234',
        );

        $this->assertEquals(400.00, $result['new_balance']);
        $this->assertEquals('transfer', $result['transaction']->type);
        $this->assertEquals('completed', $result['transaction']->status);
    }

    /** @test */
    public function it_throws_for_self_transfer()
    {
        $this->expectException(SelfTransferException::class);
        $this->service->transfer($this->sender, $this->sender->phone, 100, 'USD', '1234');
    }

    /** @test */
    public function it_throws_for_invalid_pin()
    {
        $this->expectException(InvalidPinException::class);
        $this->service->transfer($this->sender, $this->receiver->phone, 100, 'USD', 'wrong');
    }

    /** @test */
    public function it_throws_for_insufficient_balance()
    {
        $this->expectException(InsufficientBalanceException::class);
        $this->service->transfer($this->sender, $this->receiver->phone, 999999, 'USD', '1234');
    }
}
```

## WalletServiceTest

```php
class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WalletService();
    }

    /** @test */
    public function it_gets_wallet_by_user_and_currency()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id, 'currency' => 'USD',
        ]);

        $found = $this->service->getWallet($user->id, 'USD');

        $this->assertNotNull($found);
        $this->assertEquals($wallet->id, $found->id);
    }

    /** @test */
    public function it_returns_zero_balance_for_missing_wallet()
    {
        $balance = $this->service->getBalance(999, 'USD');
        $this->assertEquals(0, $balance);
    }

    /** @test */
    public function it_decrements_balance()
    {
        $wallet = Wallet::factory()->create(['balance' => 100]);

        $this->service->decrement($wallet, 50);

        $this->assertEquals(50, $wallet->fresh()->balance);
    }

    /** @test */
    public function it_throws_when_decrement_exceeds_balance()
    {
        $wallet = Wallet::factory()->create(['balance' => 10]);

        $this->expectException(\RuntimeException::class);
        $this->service->decrement($wallet, 100);
    }

    /** @test */
    public function it_increments_balance()
    {
        $wallet = Wallet::factory()->create(['balance' => 100]);

        $this->service->increment($wallet, 50);

        $this->assertEquals(150, $wallet->fresh()->balance);
    }

    /** @test */
    public function it_converts_syp_to_usd()
    {
        $usd = $this->service->convertToUsd(130000, 13000);
        $this->assertEquals(10.00, $usd);
    }

    /** @test */
    public function it_converts_usd_to_syp()
    {
        $syp = $this->service->convertToSyp(10, 13000);
        $this->assertEquals(130000, $syp);
    }
}
```
