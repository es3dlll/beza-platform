<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Enums;

enum ConsentStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
}
