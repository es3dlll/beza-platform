<?php

declare(strict_types=1);

namespace Modules\Escrow\Exceptions;

use Exception;

class EscrowExpiredException extends Exception
{
    public function __construct() { parent::__construct('Escrow agreement has expired'); }
}
