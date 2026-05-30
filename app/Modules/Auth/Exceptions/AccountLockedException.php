<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class AccountLockedException extends HttpException
{
    public function __construct(
        string $message = 'Account is locked.',
        ?\Throwable $previous = null,
        int $code = 0,
    ) {
        parent::__construct(423, $message, $previous, [], $code);
    }

    public function getErrorCode(): string
    {
        return 'AUTH_ACCOUNT_LOCKED';
    }
}
