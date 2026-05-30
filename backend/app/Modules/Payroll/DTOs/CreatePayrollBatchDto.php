<?php

declare(strict_types=1);

namespace Modules\Payroll\DTOs;

class CreatePayrollBatchDto
{
    public function __construct(
        public readonly string $employerId = '',
        public readonly string $periodMonth = '',
        public readonly ?string $notes = null,
        public readonly array $employees = [],
    ) {}
}
