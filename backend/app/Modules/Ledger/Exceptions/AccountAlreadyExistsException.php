<?php
declare(strict_types=1);

namespace Modules\Ledger\Exceptions;

use Exception;

final class AccountAlreadyExistsException extends Exception
{
    public function __construct(string $accountNumber)
    {
        parent::__construct("Account already exists: $accountNumber");
    }
}
