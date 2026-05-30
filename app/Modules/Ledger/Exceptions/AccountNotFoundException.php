<?php
declare(strict_types=1);

namespace Modules\Ledger\Exceptions;

use Exception;

final class AccountNotFoundException extends Exception
{
    public function __construct(string $accountId)
    {
        parent::__construct("Ledger account not found: $accountId");
    }
}
