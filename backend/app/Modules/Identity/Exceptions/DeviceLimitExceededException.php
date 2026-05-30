<?php

declare(strict_types=1);

namespace Modules\Identity\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class DeviceLimitExceededException extends HttpException
{
    public function __construct(
        string $message = 'Maximum number of devices reached.',
        ?\Throwable $previous = null,
        int $code = 0,
    ) {
        parent::__construct(429, $message, $previous, [], $code);
    }

    public function getErrorCode(): string
    {
        return 'AUTH_DEVICE_LIMIT';
    }
}
