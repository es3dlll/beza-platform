<?php

namespace Modules\CoreFinancialEngine\Contracts;

use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\DTOs\PostingResultDto;

interface PostingEngineInterface
{
    public function execute(PostingInstructionDto $instruction): PostingResultDto;
    public function validate(PostingInstructionDto $instruction): array;
}
