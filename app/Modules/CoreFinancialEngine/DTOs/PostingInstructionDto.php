<?php

namespace Modules\CoreFinancialEngine\DTOs;

final class PostingInstructionDto
{
    public function __construct(
        public readonly string $referenceType,
        public readonly string $referenceId,
        public readonly string $description,
        public readonly array $lines,
        public readonly ?string $channel = 'api',
        public readonly ?string $initiatedBy = null,
        public readonly ?array $metadata = [],
    ) {}
}
