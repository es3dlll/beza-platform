<?php

declare(strict_types=1);

namespace Modules\Education\Exceptions;

use Exception;

class InstitutionNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Institution not found: {$id}"); }
}
