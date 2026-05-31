<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Exceptions;

final class DeviceMismatchException extends FraudException
{
    public function __construct(string $message = 'Device not recognized, additional verification required', int $code = 6002)
    {
        parent::__construct($message, $code);
    }
}
