<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Exceptions;

use Exception;

class ConsentExpiredException extends Exception
{
    public function __construct() { parent::__construct('Consent has expired'); }
}
