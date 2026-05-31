<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class WalletNotFoundException extends RuntimeException
{
    public function __construct(string $identifier, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: "المحفظة غير موجودة: {$identifier}",
            code: 3002,
            previous: $previous,
        );
    }
}
