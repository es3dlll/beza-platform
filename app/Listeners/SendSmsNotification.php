<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

final class SendSmsNotification
{
    public function handle(object $event): void
    {
        try {
            $phone = $this->resolvePhone($event);

            if (!$phone) {
                return;
            }

            $message = $this->buildMessage($event);

            if ($message) {
                $notifications = app(\Modules\Notification\Services\NotificationService::class);
                $notifications->send('sms', $phone, $message);
            }
        } catch (\Throwable $e) {
            Log::warning('SendSmsNotification failed', [
                'event' => $event::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolvePhone(object $event): ?string
    {
        if ($event instanceof \Modules\Auth\Events\OtpGenerated) {
            return $event->phone ?? null;
        }
        if ($event instanceof \Modules\Fraud\Events\FraudTransactionBlocked) {
            if (method_exists($event, 'user') && $user = $event->user()) {
                return $user->phone;
            }
            if (isset($event->userId)) {
                $user = \Modules\Identity\Models\User::find($event->userId);
                return $user?->phone;
            }
        }
        return null;
    }

    private function buildMessage(object $event): ?string
    {
        if ($event instanceof \Modules\Auth\Events\OtpGenerated) {
            return "رمز التحقق الخاص بك في بزة: {$event->code}. صالح لمدة 5 دقائق";
        }
        if ($event instanceof \Modules\Fraud\Events\FraudTransactionBlocked) {
            return 'تنبيه: تم حظر معاملة مشبوهة في حسابك. يرجى التواصل مع الدعم الفني.';
        }
        return null;
    }
}
