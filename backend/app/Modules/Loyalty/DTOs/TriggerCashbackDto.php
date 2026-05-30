<?php

declare(strict_types=1);

namespace Modules\Loyalty\DTOs;

final class TriggerCashbackDto
{
    public function __construct(
        public readonly string $userId = '',
        public readonly int $transactionAmount = 0,
        public readonly ?string $merchantCategory = null,
        public readonly string $tierLevel = 'bronze',
    ) {}
}
