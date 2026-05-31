<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Exceptions;

final class VelocityExceededException extends FraudException
{
    public function __construct(string $message = 'Transaction velocity limit exceeded', int $code = 6003)
    {
        parent::__construct($message, $code);
    }
}
