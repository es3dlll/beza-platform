<?php

declare(strict_types=1);

namespace Modules\GovCollections\Exceptions;

use Exception;

class GovPaymentFailedException extends Exception
{
    public function __construct(string $reason) { parent::__construct("Payment failed: {$reason}"); }
}
