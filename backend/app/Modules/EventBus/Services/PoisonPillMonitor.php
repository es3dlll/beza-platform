<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Services;

use App\Modules\EventBus\Models\DeadLetterEvent;
use Illuminate\Support\Collection;

final class PoisonPillMonitor
{
    public function getDeadLetterEvents(?string $status = null): Collection
    {
        $query = DeadLetterEvent::orderBy('failed_at', 'desc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function countByStatus(): array
    {
        return [
            'pending' => DeadLetterEvent::where('status', 'pending')->count(),
            'retrying' => DeadLetterEvent::where('status', 'retrying')->count(),
            'resolved' => DeadLetterEvent::where('status', 'resolved')->count(),
            'total' => DeadLetterEvent::count(),
        ];
    }

    public function getPoisonCount(): int
    {
        return DeadLetterEvent::where('status', 'pending')->count();
    }

    public function retryDeadLetter(string $deadLetterId): void
    {
        $deadLetter = DeadLetterEvent::findOrFail($deadLetterId);
        $deadLetter->update(['status' => 'retrying']);

        // Re-dispatch the event via async handler
        $envelope = $deadLetter->payload;
        $consumerName = $deadLetter->consumer_name;
        $logId = $deadLetter->id;

        \App\Modules\EventBus\Jobs\AsyncEventHandler::dispatch($envelope, $consumerName, $logId);
    }

    public function markResolved(string $deadLetterId): void
    {
        DeadLetterEvent::where('id', $deadLetterId)->update(['status' => 'resolved']);
    }

    public function getTopConsumersWithErrors(int $limit = 5): Collection
    {
        return DeadLetterEvent::selectRaw('consumer_name, COUNT(*) as error_count')
            ->where('status', 'pending')
            ->groupBy('consumer_name')
            ->orderByDesc('error_count')
            ->limit($limit)
            ->get();
    }
}
