<?php

declare(strict_types=1);

namespace Modules\Takaful\Enums;

enum PolicyStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Lapsed = 'lapsed';
}
