<?php

declare(strict_types=1);

namespace Modules\Bills\DTOs;

class BillInquiryDto
{
    public function __construct(
        public readonly string $userId = '',
        public readonly string $billProviderId = '',
        public readonly string $accountNumber = '',
    ) {}
}
