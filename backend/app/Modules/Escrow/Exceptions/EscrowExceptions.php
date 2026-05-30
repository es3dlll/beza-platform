<?php

declare(strict_types=1);

namespace Modules\Escrow\Exceptions;

use Exception;

final class EscrowNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Escrow agreement not found: {$id}"); }
}
final class EscrowAlreadyResolvedException extends Exception
{
    public function __construct() { parent::__construct('Escrow agreement is already resolved'); }
}
final class EscrowExpiredException extends Exception
{
    public function __construct() { parent::__construct('Escrow agreement has expired'); }
}
final class EscrowDisputeNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Escrow dispute not found: {$id}"); }
}
