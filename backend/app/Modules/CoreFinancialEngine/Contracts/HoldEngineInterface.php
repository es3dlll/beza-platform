<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Contracts;

use Modules\CoreFinancialEngine\DTOs\HoldInstructionDto;
use Modules\CoreFinancialEngine\DTOs\HoldResultDto;

interface HoldEngineInterface
{
    public function place(HoldInstructionDto $dto): HoldResultDto;
    public function release(string $holdId, string $reason): HoldResultDto;
    public function validateHold(string $accountId, int $amount): bool;
}
