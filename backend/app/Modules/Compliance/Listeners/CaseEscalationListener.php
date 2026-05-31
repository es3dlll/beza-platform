<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Listeners;

use App\Modules\Compliance\Enums\AlertSeverity;
use App\Modules\Compliance\Enums\CaseStatus;
use App\Modules\Compliance\Events\AlertGenerated;
use App\Modules\Compliance\Events\CaseEscalated;
use App\Modules\Compliance\Models\Alert;
use App\Modules\Compliance\Models\ComplianceCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class CaseEscalationListener
{
    public function check(): void
    {
        $overdueCases = ComplianceCase::whereIn('status', [CaseStatus::OPEN, CaseStatus::UNDER_REVIEW])
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        foreach ($overdueCases as $case) {
            CaseStatus::assertTransition($case->status, CaseStatus::ESCALATED);

            $case->update([
                'status' => CaseStatus::ESCALATED,
                'escalated_at' => now(),
            ]);

            Event::dispatch(new CaseEscalated(
                caseId: $case->case_id,
                previousStatus: $case->getOriginal('status'),
                reason: 'Auto-escalation: 24h review window exceeded',
                timestamp: now()->getTimestamp(),
            ));

            Event::dispatch(new AlertGenerated(
                alertId: 'ALT-' . Str::ulid()->toBase32(),
                caseId: $case->case_id,
                severity: AlertSeverity::HIGH,
                message: "Case {$case->case_id} auto-escalated (24h timeout)",
                timestamp: now()->getTimestamp(),
            ));

            Log::warning('CaseEscalationListener: case escalated', [
                'case_id' => $case->case_id,
            ]);
        }
    }
}
