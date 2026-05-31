<?php

declare(strict_types=1);

namespace App\Modules\Core\Enums;

enum Currency: string
{
    case SYP = 'SYP';
    case USD = 'USD';
    case EUR = 'EUR';
    case TRY = 'TRY';
}
