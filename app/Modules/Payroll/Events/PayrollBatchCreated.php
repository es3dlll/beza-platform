<?php

declare(strict_types=1);

namespace Modules\Payroll\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PayrollBatchCreated
{
    use Dispatchable;

    public function __construct(
        public readonly string $batchId,
        public readonly string $employerId,
        public readonly int $totalEmployees,
        public readonly int $totalAmount,
    ) {}
}
