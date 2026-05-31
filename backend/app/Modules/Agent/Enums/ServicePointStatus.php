<?php

declare(strict_types=1);

namespace App\Modules\Agent\Enums;

final class ServicePointStatus
{
    const ACTIVE = 'ACTIVE';
    const SUSPENDED = 'SUSPENDED';
    const UNDER_AUDIT = 'UNDER_AUDIT';
    const CLOSED = 'CLOSED';
    const PENDING_ACTIVATION = 'PENDING_ACTIVATION';

    const OPERATIONAL = [self::ACTIVE];

    public static function canOperate(string $status): bool
    {
        return in_array(strtoupper($status), self::OPERATIONAL, true);
    }
}
