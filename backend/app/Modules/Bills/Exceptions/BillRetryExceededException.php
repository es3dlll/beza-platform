<?php

declare(strict_types=1);

namespace Modules\Bills\Exceptions;

use Exception;

final class BillRetryExceededException extends Exception
{
    public function __construct(string $account)
    {
        parent::__construct("Too many bill payment retries for account: {$account}");
    }
}
