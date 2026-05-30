<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Exceptions;

use Exception;

final class AppNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("App not found: {$id}"); }
}
