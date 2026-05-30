<?php

declare(strict_types=1);

namespace Modules\Fraud\Enums;

enum FraudDecision: string
{
    case ALLOW = 'allow';
    case FLAG = 'flag';
    case BLOCK = 'block';
    case REVIEW = 'review';
}
