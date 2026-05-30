<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Contracts\Notification\NotificationChannelInterface;

class NotificationService
{
    private array $channels = [];

    public function registerChannel(string $name, NotificationChannelInterface $channel): void
    {
        $this->channels[$name] = $channel;
    }

    public function send(string $channel, string $recipient, string $message, array $options = []): bool
    {
        if (!isset($this->channels[$channel])) {
            throw new \RuntimeException("Notification channel '{$channel}' not registered");
        }
        return $this->channels[$channel]->send($recipient, $message, $options);
    }

    public function sendOtp(string $phone, string $code, string $purpose): bool
    {
        $message = match ($purpose) {
            'register' => "رمز التحقق الخاص بك في بزة: {$code}. صالح لمدة 5 دقائق",
            'login' => "رمز تسجيل الدخول إلى بزة: {$code}. صالح لمدة 5 دقائق",
            'change_phone' => "رمز تغيير رقم الهاتف في بزة: {$code}",
            'forgot_pin' => "رمز إعادة تعيين PIN في بزة: {$code}",
            default => "رمز بزة: {$code}",
        };
        return $this->send('sms', $phone, $message);
    }
}
