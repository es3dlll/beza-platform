<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Listeners;

use App\Modules\Compliance\Events\AccountTemporarilySuspended;
use App\Modules\Compliance\Events\AlertGenerated;
use App\Modules\Compliance\Events\AutoBlockTriggered;
use App\Modules\Compliance\Models\Alert;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class AutoBlockListener
{
    public function handle(AutoBlockTriggered $event): void
    {
        Log::warning('AutoBlockListener: account suspended', [
            'account_id' => $event->accountId,
            'reason' => $event->reason,
            'risk_score' => $event->riskScore,
        ]);

        Alert::create([
            'alert_id' => 'ALT-' . Str::ulid()->toBase32(),
            'case_id' => null,
            'severity' => 'CRITICAL',
            'message' => "Account auto-blocked: {$event->reason}",
            'rule_id' => 'auto_block_engine',
            'risk_score' => $event->riskScore,
            'context' => [
                'account_id' => $event->accountId,
                'reason' => $event->reason,
            ],
            'status' => 'active',
        ]);

        Event::dispatch(new AccountTemporarilySuspended(
            accountId: $event->accountId,
            reason: $event->reason,
            timestamp: now()->getTimestamp(),
        ));
    }
}
