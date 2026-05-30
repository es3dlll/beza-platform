<?php

declare(strict_types=1);

namespace Modules\Wallet\Exceptions;

use Exception;

final class WalletNotFoundException extends Exception
{
    public function __construct(string $walletId)
    {
        parent::__construct("Wallet not found: $walletId");
    }
}
