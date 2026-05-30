<?php

declare(strict_types=1);

namespace Modules\Fraud\Enums;

enum FraudCaseStatus: string
{
    case OPEN = 'open';
    case UNDER_REVIEW = 'under_review';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';
}
