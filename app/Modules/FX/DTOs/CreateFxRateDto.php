<?php

declare(strict_types=1);

namespace Modules\FX\DTOs;

final class CreateFxRateDto
{
    public function __construct(
        public readonly string $baseCurrency,
        public readonly string $quoteCurrency,
        public readonly float $midRate,
        public readonly string $rateType,
        public readonly string $source,
        public readonly ?float $spreadPct = null,
        public readonly ?float $bidRate = null,
        public readonly ?float $askRate = null,
        public readonly ?string $validFrom = null,
        public readonly ?string $validTo = null,
    ) {}
}
