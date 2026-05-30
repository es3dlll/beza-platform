<?php

declare(strict_types=1);

namespace Modules\Agent\DTOs;

final class CashInDto
{
    public function __construct(
        public readonly string $agentId,
        public readonly string $userWalletId,
        public readonly int $amount,
        public readonly string $currency = 'SYP',
        public readonly string $referenceId = '',
        public readonly string $channel = 'agent',
        public readonly ?array $metadata = [],
    ) {}
}
