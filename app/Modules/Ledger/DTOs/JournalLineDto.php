<?php
declare(strict_types=1);

namespace Modules\Ledger\DTOs;

final class JournalLineDto
{
    public function __construct(
        public readonly string $accountId,
        public readonly int $amount,
        public readonly string $type,
        public readonly ?string $description = null,
    ) {}
}
