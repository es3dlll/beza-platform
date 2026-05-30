<?php

declare(strict_types=1);

namespace Modules\Notification\Providers;

use Modules\Notification\Services\NotificationService;
use Modules\Notification\Services\SmsChannel;
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
