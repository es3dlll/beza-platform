<?php

declare(strict_types=1);

namespace Modules\Wallet\Exceptions;

use Exception;

final class WalletFrozenException extends Exception
{
    public function __construct(string $walletId)
    {
        parent::__construct("Wallet $walletId is frozen. No operations allowed.");
    }
}
