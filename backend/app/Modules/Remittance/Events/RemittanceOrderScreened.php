<?php

declare(strict_types=1);

namespace Modules\Remittance\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class RemittanceOrderScreened
{
    use Dispatchable;

    public function __construct(
        public readonly string $remittanceOrderId,
        public readonly string $result,
        public readonly ?string $caseId = null,
    ) {}
}
