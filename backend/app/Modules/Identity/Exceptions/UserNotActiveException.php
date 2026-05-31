<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class UserNotActiveException extends RuntimeException
{
    public function __construct(string $userId, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: "المستخدم غير نشط: {$userId}",
            code: 3004,
            previous: $previous,
        );
    }
}
