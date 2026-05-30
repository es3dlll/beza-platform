<?php

declare(strict_types=1);

namespace Modules\Fraud\Exceptions;

use Exception;

class FraudIpBlockedException extends Exception
{
    public function __construct(string $ipAddress = '')
    {
        parent::__construct("IP address is associated with fraudulent activity: {$ipAddress}");
    }
}
