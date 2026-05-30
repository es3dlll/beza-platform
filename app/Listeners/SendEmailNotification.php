<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

final class SendEmailNotification
{
    public function handle(object $event): void
    {
        try {
            if (!$event instanceof \Modules\Payroll\Events\EmployerRegistered) {
                return;
            }

            $email = $event->email ?? null;
            if (!$email) {
                return;
            }

            $subject = 'Welcome to Beza Payroll';
            $message = "Your employer registration has been submitted. We will notify you once approved.\n\nThank you,\nBeza Team";

            $notifications = app(\Modules\Notification\Services\NotificationService::class);
            $notifications->send('email', $email, $message, ['subject' => $subject]);
        } catch (\Throwable $e) {
            Log::warning('SendEmailNotification failed', [
                'event' => $event::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
