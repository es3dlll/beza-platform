<?php

declare(strict_types=1);

namespace Modules\Payroll\Enums;

enum DisbursementStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
