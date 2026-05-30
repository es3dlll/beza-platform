<?php

declare(strict_types=1);

namespace Modules\Cards\DTOs;

final class AuthorizeTransactionDto
{
    public function __construct(
        public readonly string $cardId = '',
        public readonly string $userId = '',
        public readonly string $type = 'purchase',
        public readonly int $amount = 0,
        public readonly string $currency = 'SYP',
        public readonly ?string $merchantName = null,
        public readonly ?string $merchantCategory = null,
        public readonly ?string $merchantCountry = null,
        public readonly ?string $channel = null,
    ) {}
}
