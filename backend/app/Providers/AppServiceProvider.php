<?php

namespace App\Providers;

use App\Modules\Wallet\Events\TransferCompleted;
use App\Modules\Wallet\Listeners\LogTransferAudit;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('wap', function (Request $request) {
            return Limit::perMinute(30)
                ->by((string) ($request->user()?->id ?: $request->ip() ?: 'cli'));
        });

        Event::listen(
            TransferCompleted::class,
            LogTransferAudit::class,
        );
    }
}

