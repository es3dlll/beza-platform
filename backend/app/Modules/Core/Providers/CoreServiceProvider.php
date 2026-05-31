<?php

declare(strict_types=1);

namespace App\Modules\Core\Providers;

use App\Modules\Core\Console\Commands\GenerateBetaWeeklyReport;
use App\Modules\Core\Events\BetaFeedbackReceived;
use App\Modules\Core\Events\SecurityAlert;
use App\Modules\Core\Http\Controllers\BetaFeedbackController;
use App\Modules\Core\Listeners\BetaFeedbackAnalysisListener;
use App\Modules\Core\Models\BetaFeedback;
use App\Modules\Core\Services\CacheOrchestrator;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheOrchestrator::class, function ($app) {
            return new CacheOrchestrator(
                cache: $app->make(Repository::class),
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->registerRoutes();
        $this->registerListeners();

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateBetaWeeklyReport::class,
            ]);
        }
    }

    private function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('v1')
            ->group(function () {
                Route::post('/feedback/beta', [BetaFeedbackController::class, 'store']);
                Route::get('/admin/beta/feedback', [BetaFeedbackController::class, 'index']);
                Route::patch('/admin/beta/feedback/{feedbackId}', [BetaFeedbackController::class, 'update']);
                Route::get('/admin/beta/feedback/export', [BetaFeedbackController::class, 'export']);
            });

        Route::middleware(['web', 'auth'])
            ->get('/admin/beta/feedback', function () {
                return view('admin.beta.feedback');
            });
    }

    private function registerListeners(): void
    {
        Event::listen(
            BetaFeedbackReceived::class,
            BetaFeedbackAnalysisListener::class,
        );

        Event::listen(SecurityAlert::class, function (SecurityAlert $event) {
            Log::channel('audit')->critical('SECURITY_ALERT_ESCALATED', [
                'feedback_id' => $event->feedbackId,
                'user_id' => $event->userId,
                'timestamp' => $event->timestamp,
            ]);
        });
    }
}
