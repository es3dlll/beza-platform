<?php

declare(strict_types=1);

namespace Modules\Settlement\DTOs;

final class SettlementResultDto
{
    public function __construct(
        public readonly bool $success,
        public readonly string $settlementId,
        public readonly int $netAmount,
        public readonly string $cfeTransactionId,
        public readonly ?string $error = null,
    ) {}
}
