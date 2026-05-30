<?php

declare(strict_types=1);

namespace Modules\Marketplace\Enums;

enum GiftCardStatus: string
{
    case Active = 'active';
    case Delivered = 'delivered';
    case Redeemed = 'redeemed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
