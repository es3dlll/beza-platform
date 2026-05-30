<?php

declare(strict_types=1);

namespace Modules\Merchant\DTOs;

class CreateStoreDto
{
    public function __construct(
        public readonly string $merchantId = '',
        public readonly string $name = '',
        public readonly string $nameAr = '',
        public readonly string $governorate = '',
        public readonly string $city = '',
        public readonly ?string $phone = null,
        public readonly ?string $address = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
    ) {}
}
