<?php

declare(strict_types=1);

namespace Modules\Escrow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class EscrowCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $agreementId,
        public readonly string $buyerId,
        public readonly string $sellerId,
        public readonly int $amount,
    ) {}
}
