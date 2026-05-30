<?php

declare(strict_types=1);

namespace Modules\Notification\Providers;

use Modules\Notification\Services\NotificationService;
use Modules\Notification\Services\SmsChannel;
use Modules\Notification\Services\EmailChannel;
use Modules\Notification\Services\DatabaseChannel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsChannel::class);
        $this->app->singleton(EmailChannel::class);
        $this->app->singleton(DatabaseChannel::class);

        $this->app->singleton(NotificationService::class, function ($app) {
            $service = new NotificationService();
            $service->registerChannel('sms', $app->make(SmsChannel::class));
            $service->registerChannel('email', $app->make(EmailChannel::class));
            $service->registerChannel('database', $app->make(DatabaseChannel::class));
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
