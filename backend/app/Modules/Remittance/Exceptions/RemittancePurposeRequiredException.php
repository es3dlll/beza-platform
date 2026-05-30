<?php

declare(strict_types=1);

namespace Modules\Remittance\Exceptions;

use Exception;

final class RemittancePurposeRequiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('A valid purpose code is required for this remittance');
    }
}
