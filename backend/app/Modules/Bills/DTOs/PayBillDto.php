<?php

declare(strict_types=1);

namespace Modules\Bills\DTOs;

final class PayBillDto
{
    public function __construct(
        public readonly string $billPaymentId = '',
        public readonly int $amount = 0,
    ) {}
}
