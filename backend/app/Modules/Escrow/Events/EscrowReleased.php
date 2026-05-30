<?php

declare(strict_types=1);

namespace Modules\Escrow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class EscrowReleased
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $agreementId,
        public readonly string $cfeHoldId,
    ) {}
}
