<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class UserNotFoundException extends RuntimeException
{
    public function __construct(string $identifier, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: "المستخدم غير موجود: {$identifier}",
            code: 3001,
            previous: $previous,
        );
    }
}
