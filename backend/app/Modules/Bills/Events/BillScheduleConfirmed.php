<?php

declare(strict_types=1);

namespace App\Modules\Bills\Events;

use App\Modules\Bills\Models\ScheduledPayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class BillScheduleConfirmed
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public ScheduledPayment $schedule,
    ) {}
}
