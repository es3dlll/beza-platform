<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Listeners;

use App\Modules\Compliance\Events\ListUpdatedEvent;
use App\Modules\Compliance\Events\TransactionCompleted;
use App\Modules\Compliance\Models\SanctionList;
use App\Modules\Compliance\Services\SanctionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class SanctionListUpdaterListener
{
    public function __construct(private SanctionService $sanctionService) {}

    public function handle(TransactionCompleted $event): void
    {
        // فحص المعاملة ضد القائمة المحظورة
        $hits = $this->sanctionService->check(
            name: $event->recipientId,
            phone: null,
            deviceFingerprint: $event->deviceFingerprint,
        );

        if (count($hits) > 0) {
            Log::warning('Sanction hit detected', [
                'transaction_id' => $event->transactionId,
                'hits' => $hits,
            ]);
        }
    }

    public function updateList(): void
    {
        if (!$this->isUpdateDue()) {
            return;
        }

        Log::info('SanctionListUpdaterListener: updating sanction list');

        // محاكاة تحديث القائمة من مصدر آمن
        Cache::forget(SanctionService::CACHE_KEY);

        Event::dispatch(new ListUpdatedEvent(
            source: 'internal',
            recordsCount: SanctionList::where('active', true)->count(),
            timestamp: now()->getTimestamp(),
        ));
    }

    private function isUpdateDue(): bool
    {
        $lastUpdate = Cache::get('sanction_list_last_update');
        return !$lastUpdate || (now()->getTimestamp() - $lastUpdate) > 21600;
    }
}
