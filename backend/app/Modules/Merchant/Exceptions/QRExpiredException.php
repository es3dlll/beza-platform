<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Exceptions;

final class QRExpiredException extends MerchantException
{
    public function __construct(string $detail = 'QR code expired or invalid')
    {
        parent::__construct($detail);
    }
}
