<?php

declare(strict_types=1);

namespace Modules\Marketplace\Enums;

enum OrderStatus: string
{
    case Cart = 'cart';
    case Pending = 'pending';
    case Paid = 'paid';
    case Fulfilling = 'fulfilling';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
}
