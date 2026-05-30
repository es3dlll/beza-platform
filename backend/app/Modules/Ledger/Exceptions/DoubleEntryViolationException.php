<?php
declare(strict_types=1);

namespace Modules\Ledger\Exceptions;

use Exception;

final class DoubleEntryViolationException extends Exception
{
    public function __construct(
        public readonly int $debitTotal,
        public readonly int $creditTotal,
    ) {
        parent::__construct('Journal entry must be balanced: debits must equal credits');
    }
}
