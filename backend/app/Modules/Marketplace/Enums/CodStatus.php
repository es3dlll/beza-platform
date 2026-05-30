<?php

declare(strict_types=1);

namespace Modules\Marketplace\Enums;

enum CodStatus: string
{
    case Pending = 'pending';
    case Collected = 'collected';
    case Remitted = 'remitted';
    case Disputed = 'disputed';
}
