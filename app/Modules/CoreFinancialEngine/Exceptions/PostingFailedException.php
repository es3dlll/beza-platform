<?php

namespace Modules\CoreFinancialEngine\Exceptions;

use Exception;

final class PostingFailedException extends Exception
{
    public function __construct(
        public readonly string $reason,
        public readonly ?string $errorCode = null,
    ) {
        parent::__construct("Posting failed: $reason");
    }
}
