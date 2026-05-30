<?php

declare(strict_types=1);

namespace Modules\Identity\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class OtpExpiredException extends HttpException
{
    public function __construct(
        string $message = 'OTP has expired or is invalid.',
        ?\Throwable $previous = null,
        int $code = 0,
    ) {
        parent::__construct(400, $message, $previous, [], $code);
    }

    public function getErrorCode(): string
    {
        return 'AUTH_INVALID_OTP';
    }
}
