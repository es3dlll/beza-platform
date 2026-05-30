<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class AuthenticationException extends HttpException
{
    public function __construct(
        string $message = 'Authentication failed.',
        ?\Throwable $previous = null,
        int $code = 0,
    ) {
        parent::__construct(401, $message, $previous, [], $code);
    }

    public function getErrorCode(): string
    {
        return 'AUTH_FAILED';
    }
}
