<?php

declare(strict_types=1);

namespace Modules\Remittance\DTOs;

final class CreateCorridorDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $sourceCountry,
        public readonly string $sourceCurrency,
        public readonly string $targetCurrency = 'SYP',
        public readonly string $fxRateSource = 'cbs_official',
        public readonly float $fixedSpreadPct = 2.0,
        public readonly string $feeType = 'percentage',
        public readonly ?array $feeStructure = null,
        public readonly int $minAmount = 25000,
        public readonly int $maxAmount = 10000000,
        public readonly int $dailyLimitPerSender = 50000000,
        public readonly int $monthlyLimitPerSender = 250000000,
        public readonly bool $isActive = true,
        public readonly array $supportedPayoutMethods = ['wallet'],
        public readonly ?array $complianceRequirements = null,
        public readonly ?string $partnerName = null,
    ) {}
}
