<?php

declare(strict_types=1);

namespace Modules\Loyalty\Enums;

enum TierLevel: string
{
    case BRONZE = 'bronze';
    case SILVER = 'silver';
    case GOLD = 'gold';
    case PLATINUM = 'platinum';
}
