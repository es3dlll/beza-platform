<?php

declare(strict_types=1);

namespace Modules\Identity\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class EmailAlreadyRegisteredException extends HttpException
{
    public function __construct(
        string $message = 'This email is already registered.',
        ?\Throwable $previous = null,
        int $code = 0,
    ) {
        parent::__construct(409, $message, $previous, [], $code);
    }

    public function getErrorCode(): string
    {
        return 'AUTH_EMAIL_EXISTS';
    }
}
