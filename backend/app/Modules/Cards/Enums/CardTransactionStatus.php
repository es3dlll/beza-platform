<?php

declare(strict_types=1);

namespace Modules\Cards\Enums;

enum CardTransactionStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DECLINED = 'declined';
    case REFUNDED = 'refunded';
    case SETTLED = 'settled';
}
