<?php

declare(strict_types=1);

namespace Modules\Fraud\Enums;

enum FraudSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';
}
