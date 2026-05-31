<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Listeners;

use App\Modules\Remittance\Services\RemittanceEngine;
use Illuminate\Support\Facades\Log;

final class ExpiryListener
{
    public function __construct(
        private readonly RemittanceEngine $engine,
    ) {}

    public function check(): void
    {
        $count = $this->engine->expirePendingTransfers();

        if ($count > 0) {
            Log::channel('audit')->info('EXPIRED_TRANSFERS_CLEANED', [
                'count' => $count,
            ]);
        }
    }
}
