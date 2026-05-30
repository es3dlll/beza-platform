<?php

declare(strict_types=1);

namespace Modules\Payroll\Enums;

enum PayrollBatchStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case PARTIALLY_FAILED = 'partially_failed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
