<?php

declare(strict_types=1);

namespace Modules\Education\Enums;

enum EducationFeeStatus: string
{
    case PENDING = 'pending';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
}
