<?php

declare(strict_types=1);

namespace Modules\Merchant\Exceptions;

use Exception;

final class MerchantRefundExpiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('Refund window has expired (7 days from transaction)');
    }
}
