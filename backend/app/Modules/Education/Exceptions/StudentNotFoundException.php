<?php

declare(strict_types=1);

namespace Modules\Education\Exceptions;

use Exception;

final class StudentNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Student not found: {$id}"); }
}
