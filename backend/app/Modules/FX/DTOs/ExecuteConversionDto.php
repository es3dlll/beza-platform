<?php

declare(strict_types=1);

namespace Modules\FX\DTOs;

final class ExecuteConversionDto
{
    public function __construct(
        public readonly string $quoteId,
        public readonly ?string $fromWalletId = null,
        public readonly ?string $toWalletId = null,
    ) {}
}
