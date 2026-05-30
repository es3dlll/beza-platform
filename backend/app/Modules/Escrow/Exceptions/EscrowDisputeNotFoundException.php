<?php

declare(strict_types=1);

namespace Modules\Escrow\Exceptions;

use Exception;

final class EscrowDisputeNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Escrow dispute not found: {$id}"); }
}
