<?php

declare(strict_types=1);

namespace Modules\Escrow\Exceptions;

use Exception;

class EscrowNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Escrow agreement not found: {$id}"); }
}
