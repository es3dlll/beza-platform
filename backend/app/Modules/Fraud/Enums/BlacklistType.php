<?php

declare(strict_types=1);

namespace Modules\Fraud\Enums;

enum BlacklistType: string
{
    case IP = 'ip';
    case DEVICE = 'device';
    case PHONE = 'phone';
    case EMAIL = 'email';
    case IBAN = 'iban';
}
