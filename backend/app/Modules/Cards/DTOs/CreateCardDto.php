<?php

declare(strict_types=1);

namespace Modules\Cards\DTOs;

final class CreateCardDto
{
    public function __construct(
        public readonly string $userId = '',
        public readonly string $cardType = 'virtual',
        public readonly string $cardholderName = '',
        public readonly string $currency = 'SYP',
        public readonly bool $isVirtual = true,
    ) {}
}
