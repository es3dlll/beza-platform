<?php

declare(strict_types=1);

namespace Modules\Bills\Exceptions;

use Exception;

final class BillAccountFormatInvalidException extends Exception
{
    public function __construct(string $account, string $expectedFormat)
    {
        parent::__construct("Account format invalid for biller: {$account} (expected {$expectedFormat})");
    }
}
