<?php

declare(strict_types=1);

namespace App\Modules\Notification\Events;

use App\Modules\Notification\Models\NotificationMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class NotificationDispatched
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public NotificationMessage $message,
    ) {}
}
