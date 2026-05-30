<?php
declare(strict_types=1);

namespace Modules\Ledger\DTOs;

final class CreateAccountDto
{
    public function __construct(
        public readonly string $accountNumber,
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $currency = 'SYP',
        public readonly ?string $parentId = null,
        public readonly ?array $metadata = [],
    ) {}
}
