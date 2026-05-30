<?php

declare(strict_types=1);

namespace Modules\Cards\Enums;

enum CardStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case BLOCKED = 'blocked';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
}
