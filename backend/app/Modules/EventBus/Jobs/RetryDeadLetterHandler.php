<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Jobs;

use App\Modules\EventBus\Models\DeadLetterEvent;
use App\Modules\EventBus\Services\PoisonPillMonitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class RetryDeadLetterHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(PoisonPillMonitor $monitor): void
    {
        $deadLetters = DeadLetterEvent::where('status', 'pending')
            ->orderBy('failed_at', 'asc')
            ->limit(10)
            ->get();

        foreach ($deadLetters as $deadLetter) {
            try {
                Log::info("Retrying dead letter event: {$deadLetter->event_id}");

                $monitor->retryDeadLetter($deadLetter->id);
            } catch (\Throwable $e) {
                Log::error("Failed to retry dead letter {$deadLetter->id}: {$e->getMessage()}");
            }
        }
    }
}
