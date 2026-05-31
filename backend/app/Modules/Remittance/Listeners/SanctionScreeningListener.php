<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Listeners;

use App\Modules\Remittance\Enums\ComplianceTier;
use App\Modules\Remittance\Enums\TransferStatus;
use App\Modules\Remittance\Events\FXRateLocked;
use App\Modules\Remittance\Exceptions\ComplianceBlockedException;
use App\Modules\Remittance\Models\Remittance;
use App\Modules\Remittance\Services\RemittanceEngine;
use Illuminate\Support\Facades\Log;

final class SanctionScreeningListener
{
    private const SANCTIONED_COUNTRIES = ['IR', 'KP', 'CU', 'SY']; // مثال: دول محظورة
    private const SANCTIONED_PREFIXES = ['+963']; // أرقام سورية مسموح بها

    public function __construct(
        private readonly RemittanceEngine $engine,
    ) {}

    public function handle(FXRateLocked $event): void
    {
        $remittance = Remittance::where('remittance_id', $event->remittanceId)->first();

        if (!$remittance) {
            return;
        }

        try {
            $this->screen($remittance);
            $this->engine->runComplianceCheck($event->remittanceId);
        } catch (ComplianceBlockedException $e) {
            $remittance->update([
                'status' => TransferStatus::REJECTED,
                'cancellation_reason' => $e->getMessage(),
            ]);
            Log::channel('audit')->warning('SANCTION_BLOCKED', [
                'remittance_id' => $event->remittanceId,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    private function screen(Remittance $remittance): void
    {
        if (in_array($remittance->recipient_country, self::SANCTIONED_COUNTRIES, true)) {
            throw new ComplianceBlockedException("Destination country {$remittance->recipient_country} is sanctioned");
        }

        $remittance->update(['compliance_tier' => ComplianceTier::LOW]);
    }
}
