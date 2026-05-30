<?php
declare(strict_types=1);

namespace Modules\Ledger\DTOs;

final class TransactionResultDto
{
    public function __construct(
        public readonly bool $success,
        public readonly string $transactionId,
        public readonly string $journalEntryId,
        public readonly array $affectedAccounts,
        public readonly ?string $error = null,
    ) {}
}
