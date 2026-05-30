<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Notification\Contracts\NotificationChannelInterface;

final class EmailChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $message, array $options = []): bool
    {
        $subject = $options['subject'] ?? 'Beza Notification';

        try {
            Mail::raw($message, function ($mail) use ($recipient, $subject): void {
                $mail->to($recipient)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            return true;
        } catch (\Throwable $e) {
            Log::error('[Email] Failed to send', ['to' => $recipient, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function getType(): string
    {
        return 'email';
    }
}
