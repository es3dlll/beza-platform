<?php

declare(strict_types=1);

namespace Modules\GovCollections\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class GovPaymentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $paymentId,
        public readonly string $userId,
        public readonly string $serviceType,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $reference,
    ) {}
}
