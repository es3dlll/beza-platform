<?php

declare(strict_types=1);

namespace Modules\Payroll\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PayrollDisbursementFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $disbursementId,
        public readonly string $batchId,
        public readonly string $employeePhone,
        public readonly int $amount,
        public readonly string $reason,
    ) {}
}
