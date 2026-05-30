<?php

declare(strict_types=1);

namespace Modules\Ledger\Contracts;

use App\Domain\ValueObjects\Money;
use Modules\Ledger\DTOs\CreateAccountDto;
use Modules\Ledger\Models\LedgerAccount;

interface AccountServiceInterface
{
    public function create(CreateAccountDto $dto): LedgerAccount;

    public function getBalance(string $accountId): Money;

    public function getAvailableBalance(string $accountId): Money;

    public function adjustBalance(string $accountId, int $amount, string $direction, string $journalEntryId): LedgerAccount;
}
