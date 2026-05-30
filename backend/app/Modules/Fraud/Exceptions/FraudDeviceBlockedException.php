<?php

declare(strict_types=1);

namespace Modules\Fraud\Exceptions;

use Exception;

class FraudDeviceBlockedException extends Exception
{
    public function __construct(string $deviceId = '')
    {
        parent::__construct("Device is blacklisted due to previous fraud activity: {$deviceId}");
    }
}
