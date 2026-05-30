<?php

declare(strict_types=1);

namespace Modules\Ledger\Contracts;

use Modules\Ledger\DTOs\CreateHoldDto;
use Modules\Ledger\Models\LedgerHold;

interface HoldServiceInterface
{
    public function place(CreateHoldDto $dto): LedgerHold;

    public function release(string $holdId, string $reason): LedgerHold;

    public function releaseExpired(): int;
}
