<?php

declare(strict_types=1);

namespace Modules\Education\Exceptions;

use Exception;

final class InstitutionNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Institution not found: {$id}"); }
}
final class StudentNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Student not found: {$id}"); }
}
final class FeeAlreadyPaidException extends Exception
{
    public function __construct() { parent::__construct('Fee is already fully paid'); }
}
