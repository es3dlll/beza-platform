<?php

declare(strict_types=1);

namespace Modules\Payroll\Events;

use Illuminate\Foundation\Events\Dispatchable;

class EmployerRegistered
{
    use Dispatchable;

    public function __construct(
        public readonly string $employerId,
        public readonly string $userId,
        public readonly string $companyName,
    ) {}
}
