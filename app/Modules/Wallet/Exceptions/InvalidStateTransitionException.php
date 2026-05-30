<?php

declare(strict_types=1);

namespace Modules\Wallet\Exceptions;

use Exception;

final class InvalidStateTransitionException extends Exception
{
    public function __construct(
        public readonly string $currentStatus,
        public readonly string $attemptedAction,
        public readonly string $walletId,
    ) {
        parent::__construct(
            "Cannot $attemptedAction on wallet $walletId in status $currentStatus"
        );
    }
}
