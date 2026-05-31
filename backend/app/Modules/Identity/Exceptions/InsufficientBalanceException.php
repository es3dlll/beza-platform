<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class InsufficientBalanceException extends RuntimeException
{
    public function __construct(string $walletId, int $requested, int $available, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: "الرصيد غير كافٍ في المحفظة {$walletId}: المطلوب {$requested}، المتوفر {$available}",
            code: 3003,
            previous: $previous,
        );
    }
}
