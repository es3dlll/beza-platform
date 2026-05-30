<?php

declare(strict_types=1);

namespace Modules\Bills\Exceptions;

use Exception;

final class BillNotFoundException extends Exception
{
    public function __construct(string $account)
    {
        parent::__construct("Biller or account not found: {$account}");
    }
}
