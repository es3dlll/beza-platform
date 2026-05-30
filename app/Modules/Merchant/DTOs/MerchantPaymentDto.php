<?php

declare(strict_types=1);

namespace Modules\Merchant\DTOs;

class MerchantPaymentDto
{
    public function __construct(
        public readonly string $qrCode = '',
        public readonly string $merchantId = '',
        public readonly string $payerUserId = '',
        public readonly int $amount = 0,
        public readonly ?string $storeId = null,
    ) {}
}
