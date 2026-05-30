<?php

declare(strict_types=1);

namespace Modules\Cards\Enums;

enum CardType: string
{
    case VIRTUAL = 'virtual';
    case PREPAID = 'prepaid';
    case DEBIT = 'debit';
}
