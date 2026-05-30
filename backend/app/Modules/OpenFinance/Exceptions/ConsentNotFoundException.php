<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Exceptions;

use Exception;

final class ConsentNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Consent not found: {$id}"); }
}
