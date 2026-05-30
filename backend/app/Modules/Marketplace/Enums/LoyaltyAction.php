<?php

declare(strict_types=1);

namespace Modules\Marketplace\Enums;

enum LoyaltyAction: string
{
    case Purchase = 'purchase';
    case Referral = 'referral';
    case Redemption = 'redemption';
    case Bonus = 'bonus';
}
