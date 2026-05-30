<?php

declare(strict_types=1);

namespace Modules\Savings\Enums;

enum SavingsGoalStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
