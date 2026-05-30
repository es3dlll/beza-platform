<?php
declare(strict_types=1);

namespace Modules\Ledger\Tests;

use Modules\Ledger\DTOs\CreateAccountDto;
use Modules\Ledger\Exceptions\AccountAlreadyExistsException;
use Modules\Ledger\Models\LedgerAccount;
use Modules\Ledger\Services\AccountService;
use Tests\TestCase;

final class AccountServiceTest extends TestCase
{
    private AccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(AccountService::class);
    }

    public function test_can_create_account(): void
    {
        $dto = new CreateAccountDto(
            accountNumber: '1000-001',
            name: 'Test Account',
            type: 'asset',
            currency: 'SYP',
        );

        $account = $this->service->create($dto);

        $this->assertInstanceOf(LedgerAccount::class, $account);
        $this->assertEquals('1000-001', $account->account_number);
        $this->assertEquals(0, $account->balance);
    }

    public function test_cannot_create_duplicate_account(): void
    {
        $dto = new CreateAccountDto(
            accountNumber: '1000-002',
            name: 'Duplicate',
            type: 'asset',
        );

        $this->service->create($dto);

        $this->expectException(AccountAlreadyExistsException::class);
        $this->service->create($dto);
    }

    public function test_balance_starts_at_zero(): void
    {
        $dto = new CreateAccountDto(
            accountNumber: '1000-003',
            name: 'Zero Balance',
            type: 'asset',
        );

        $account = $this->service->create($dto);
        $money = $this->service->getBalance($account->id);

        $this->assertEquals(0, $money->toInt());
    }
}
