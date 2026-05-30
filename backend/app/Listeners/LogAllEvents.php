<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

final class LogAllEvents
{
    public function handle(object $event): void
    {
        Log::debug(class_basename($event), [
            'event' => $event::class,
            'data' => method_exists($event, 'toLog') ? $event->toLog() : [],
        ]);
    }
}
