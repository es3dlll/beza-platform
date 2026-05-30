<?php

declare(strict_types=1);

namespace Modules\FX\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FxQuoteCreated
{
    use Dispatchable;

    public function __construct(
        public readonly string $quoteId,
        public readonly string $requestorId,
        public readonly string $requestorType,
        public readonly string $baseCurrency,
        public readonly string $quoteCurrency,
        public readonly int $amountInBase,
        public readonly int $amountInQuote,
        public readonly float $rateUsed,
    ) {}
}
