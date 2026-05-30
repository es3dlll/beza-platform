<?php

declare(strict_types=1);

namespace Modules\FX\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FxQuoteAccepted
{
    use Dispatchable;

    public function __construct(
        public readonly string $quoteId,
        public readonly string $conversionId,
    ) {}
}
