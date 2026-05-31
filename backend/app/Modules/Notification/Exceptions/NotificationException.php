<?php

declare(strict_types=1);

namespace App\Modules\Notification\Exceptions;

final class NotificationException extends \RuntimeException
{
    public function __construct(string $message, int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
