<?php

declare(strict_types=1);

namespace Modules\Savings\Enums;

enum ProfitDistributionMethod: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case MATURITY = 'maturity';
}
