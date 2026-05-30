<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Contracts;

use Modules\CoreFinancialEngine\DTOs\FeeAssessmentDto;
use Modules\CoreFinancialEngine\DTOs\FeeResultDto;

interface FeeEngineInterface
{
    public function calculate(FeeAssessmentDto $dto): FeeResultDto;
    public function apply(FeeAssessmentDto $dto): FeeResultDto;
}
