<?php

declare(strict_types=1);

namespace Modules\Bills\DTOs;

final class CreateBillProviderDto
{
    public function __construct(
        public readonly string $code = '',
        public readonly string $name = '',
        public readonly string $nameAr = '',
        public readonly string $category = '',
        public readonly string $accountLabel = '',
        public readonly ?string $accountFormatRegex = null,
        public readonly ?array $supportedAccountTypes = null,
        public readonly float $feePercentage = 0.5,
        public readonly int $feeMinSyp = 100,
        public readonly int $feeMaxSyp = 2000,
        public readonly bool $isActive = true,
        public readonly ?array $integrationConfig = null,
    ) {}
}
