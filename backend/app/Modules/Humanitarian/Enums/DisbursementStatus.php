<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Enums;

enum DisbursementStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DISBURSED = 'disbursed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
