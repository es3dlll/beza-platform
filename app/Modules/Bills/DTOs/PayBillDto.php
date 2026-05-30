<?php

declare(strict_types=1);

namespace Modules\Bills\DTOs;

class PayBillDto
{
    public function __construct(
        public readonly string $billPaymentId = '',
        public readonly int $amount = 0,
    ) {}
}
