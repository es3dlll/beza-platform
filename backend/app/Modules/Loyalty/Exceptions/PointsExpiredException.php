<?php

declare(strict_types=1);

namespace Modules\Loyalty\Exceptions;

use Exception;

class PointsExpiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('Points have expired and cannot be used');
    }
}
