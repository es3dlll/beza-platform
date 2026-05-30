<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Exceptions;

use Exception;

class ProgramNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Program not found: {$id}"); }
}
