<?php

namespace Modules\CoreFinancialEngine\DTOs;

final class FeeResultDto implements \JsonSerializable
{
    public function __construct(
        public readonly bool $applied,
        public readonly int $feeAmount,
        public readonly string $currency,
        public readonly string $feeAccountId,
        public readonly ?string $journalEntryId = null,
        public readonly ?string $feeRule = null,
        public readonly ?string $error = null,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'applied' => $this->applied,
            'fee_amount' => $this->feeAmount,
            'currency' => $this->currency,
            'fee_account_id' => $this->feeAccountId,
            'journal_entry_id' => $this->journalEntryId,
            'fee_rule' => $this->feeRule,
            'error' => $this->error,
        ];
    }
}
