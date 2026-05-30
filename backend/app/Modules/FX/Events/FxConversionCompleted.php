<?php

declare(strict_types=1);

namespace Modules\FX\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FxConversionCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $conversionId,
        public readonly string $quoteId,
        public readonly string $fromCurrency,
        public readonly string $toCurrency,
        public readonly int $fromAmount,
        public readonly int $toAmount,
        public readonly int $feeAmount,
    ) {}
}
