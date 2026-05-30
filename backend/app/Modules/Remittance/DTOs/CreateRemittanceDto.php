<?php

declare(strict_types=1);

namespace Modules\Remittance\DTOs;

final class CreateRemittanceDto
{
    public function __construct(
        public readonly string $corridorId = '',
        public readonly string $senderUserId = '',
        public readonly string $senderCountry = '',
        public readonly string $senderFullName = '',
        public readonly string $senderPhone = '',
        public readonly ?string $senderIdDocument = null,
        public readonly string $beneficiaryId = '',
        public readonly int $sourceAmount = 0,
        public readonly string $sourceCurrency = '',
        public readonly string $payoutMethod = '',
        public readonly string $purposeCode = '',
        public readonly string $sourceOfFundsDeclaration = '',
        public readonly ?string $payoutWalletId = null,
        public readonly ?string $payoutAgentId = null,
        public readonly ?string $payoutBankAccount = null,
    ) {}
}
