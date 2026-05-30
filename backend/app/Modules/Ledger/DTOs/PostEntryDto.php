<?php
declare(strict_types=1);

namespace Modules\Ledger\DTOs;

final class PostEntryDto
{
    public function __construct(
        public readonly string $referenceType,
        public readonly string $referenceId,
        public readonly string $description,
        public readonly array $lines,
        public readonly ?\DateTimeInterface $postedAt = null,
    ) {}
}
