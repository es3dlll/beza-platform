<?php

declare(strict_types=1);

namespace Modules\Escrow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class EscrowDisputed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $disputeId,
        public readonly string $escrowId,
        public readonly string $reason,
    ) {}
}
