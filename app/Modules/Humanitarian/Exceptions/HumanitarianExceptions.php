<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Exceptions;

use Exception;

class OrganizationNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Organization not found: {$id}"); }
}
class ProgramNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Program not found: {$id}"); }
}
class InsufficientBudgetException extends Exception
{
    public function __construct() { parent::__construct('Insufficient program budget'); }
}
