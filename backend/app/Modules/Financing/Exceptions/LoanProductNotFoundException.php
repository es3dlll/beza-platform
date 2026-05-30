<?php

declare(strict_types=1);

namespace Modules\Financing\Exceptions;

use Exception;

class LoanProductNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Loan product not found: {$id}"); }
}
