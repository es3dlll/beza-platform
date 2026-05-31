<?php

declare(strict_types=1);

namespace App\Modules\BillProvider\Listeners;

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\BillProvider\Events\BillProviderDeactivated;
use App\Modules\BillProvider\Events\BillProviderRegistered;

final class LogBillProviderActivity
{
    public function handle(BillProviderRegistered|BillProviderDeactivated $event): void
    {
        $action = match ($event::class) {
            BillProviderRegistered::class => 'provider_registered',
            BillProviderDeactivated::class => 'provider_deactivated',
            default => 'provider_updated',
        };

        AuditLog::create([
            'user_id' => $event->provider->id,
            'action' => $action,
            'resource_type' => 'bill_provider',
            'resource_id' => $event->provider->id,
            'result' => 'success',
            'metadata' => [
                'provider_name' => $event->provider->name,
                'category' => $event->provider->category,
            ],
        ]);
    }
}
