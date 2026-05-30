<?php

declare(strict_types=1);

namespace Modules\Escrow\Enums;

enum EscrowStatus: string
{
    case PENDING = 'pending';
    case HELD = 'held';
    case RELEASED = 'released';
    case REFUNDED = 'refunded';
    case DISPUTED = 'disputed';
    case RESOLVED = 'resolved';
    case EXPIRED = 'expired';
}
