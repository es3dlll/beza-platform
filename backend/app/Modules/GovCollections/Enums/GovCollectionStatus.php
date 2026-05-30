<?php

declare(strict_types=1);

namespace Modules\GovCollections\Enums;

enum GovCollectionStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
}
