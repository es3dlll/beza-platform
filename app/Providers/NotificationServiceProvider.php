<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Notification\NotificationChannelInterface;
use App\Services\Notification\NotificationService;
use App\Services\Notification\SmsChannel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsChannel::class, function () {
            return new SmsChannel();
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            $service = new NotificationService();
            $service->registerChannel('sms', $app->make(SmsChannel::class));
            return $service;
        });
    }

    public function boot(): void
    {
        Event::listen(
            \Modules\Auth\Events\OtpGenerated::class,
            \Modules\Auth\Listeners\SendOtpNotification::class,
        );
    }
}
