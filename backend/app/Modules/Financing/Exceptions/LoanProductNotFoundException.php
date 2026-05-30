<?php

declare(strict_types=1);

namespace Modules\Financing\Exceptions;

use Exception;

final class LoanProductNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Loan product not found: {$id}"); }
}
