<?php

declare(strict_types=1);

namespace Modules\Merchant\Exceptions;

use Exception;

final class MerchantQrExpiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('Dynamic QR code has expired');
    }
}
