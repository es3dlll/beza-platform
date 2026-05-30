<?php

declare(strict_types=1);

namespace Modules\Bills\Exceptions;

use Exception;

final class BillInquiryFailedException extends Exception
{
    public function __construct(string $reason = 'Biller API error')
    {
        parent::__construct("Bill inquiry failed: {$reason}");
    }
}
