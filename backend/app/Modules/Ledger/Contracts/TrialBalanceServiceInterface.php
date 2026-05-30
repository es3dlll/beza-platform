<?php

declare(strict_types=1);

namespace Modules\Ledger\Contracts;

interface TrialBalanceServiceInterface
{
    public function generate(): array;
}
