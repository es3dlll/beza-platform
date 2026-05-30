<?php

declare(strict_types=1);

namespace Modules\FX\DTOs;

final class GetQuoteDto
{
    public function __construct(
        public readonly string $requestorId,
        public readonly string $requestorType,
        public readonly string $baseCurrency,
        public readonly string $quoteCurrency,
        public readonly int $amount,
        public readonly string $rateType = 'cbs_official',
        public readonly ?int $ttlSeconds = 60,
    ) {}
}
