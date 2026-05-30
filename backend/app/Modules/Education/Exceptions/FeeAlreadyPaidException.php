<?php

declare(strict_types=1);

namespace Modules\Education\Exceptions;

use Exception;

final class FeeAlreadyPaidException extends Exception
{
    public function __construct() { parent::__construct('Fee is already fully paid'); }
}
