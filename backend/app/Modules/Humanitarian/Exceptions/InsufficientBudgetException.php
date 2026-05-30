<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Exceptions;

use Exception;

final class InsufficientBudgetException extends Exception
{
    public function __construct() { parent::__construct('Insufficient program budget'); }
}
