<?php

declare(strict_types=1);

namespace Modules\Escrow\Exceptions;

use Exception;

class EscrowNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Escrow agreement not found: {$id}"); }
}
class EscrowAlreadyResolvedException extends Exception
{
    public function __construct() { parent::__construct('Escrow agreement is already resolved'); }
}
class EscrowExpiredException extends Exception
{
    public function __construct() { parent::__construct('Escrow agreement has expired'); }
}
class EscrowDisputeNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Escrow dispute not found: {$id}"); }
}
