<?php

declare(strict_types=1);

namespace Modules\Merchant\Exceptions;

use Exception;

final class MerchantNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Merchant not found: {$id}");
    }
}
