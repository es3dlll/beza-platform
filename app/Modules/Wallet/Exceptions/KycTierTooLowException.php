<?php

declare(strict_types=1);

namespace Modules\Wallet\Exceptions;

use Exception;

final class KycTierTooLowException extends Exception
{
    public function __construct(
        public readonly int $currentTier,
        public readonly int $requiredTier,
    ) {
        parent::__construct("KYC tier $currentTier is too low. Minimum required: $requiredTier");
    }
}
