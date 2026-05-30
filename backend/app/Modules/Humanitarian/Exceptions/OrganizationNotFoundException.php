<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Exceptions;

use Exception;

class OrganizationNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Organization not found: {$id}"); }
}
