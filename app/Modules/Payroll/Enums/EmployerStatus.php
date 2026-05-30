<?php

declare(strict_types=1);

namespace Modules\Payroll\Enums;

enum EmployerStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';
}
