<?php

declare(strict_types=1);

namespace Modules\Auth\Listeners;

use Modules\Notification\Services\NotificationService;
use Modules\Auth\Events\OtpGenerated;

class SendOtpNotification
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(OtpGenerated $event): void
    {
        $this->notifications->sendOtp($event->phone, $event->code, $event->purpose);
    }
}
