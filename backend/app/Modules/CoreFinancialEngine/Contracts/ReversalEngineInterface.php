<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Contracts;

use Modules\CoreFinancialEngine\DTOs\ReversalInstructionDto;
use Modules\CoreFinancialEngine\DTOs\PostingResultDto;

interface ReversalEngineInterface
{
    public function reverse(ReversalInstructionDto $dto): PostingResultDto;
    public function canReverse(string $originalTransactionId): array;
}
