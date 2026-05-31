<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::prefix('v1/core')->group(function (): void {
    Route::get('/health', function () {
        $checks = [];
        $healthy = true;

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'healthy', 'latency_ms' => 0];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
            $healthy = false;
        }

        // Cache check
        try {
            Cache::store('database')->set('health_ping', true, 1);
            $cacheOk = Cache::store('database')->get('health_ping') === true;
            $checks['cache'] = ['status' => $cacheOk ? 'healthy' : 'degraded'];
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
            $healthy = false;
        }

        // Storage check
        try {
            Storage::disk('local')->put('health_check.txt', 'ok');
            $storageOk = Storage::disk('local')->exists('health_check.txt');
            Storage::disk('local')->delete('health_check.txt');
            $checks['storage'] = ['status' => $storageOk ? 'healthy' : 'degraded'];
        } catch (\Exception $e) {
            $checks['storage'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
            $healthy = false;
        }

        // Queue check — verify the jobs table exists
        try {
            $queueTableExists = DB::connection()->getSchemaBuilder()->hasTable('jobs');
            $checks['queue'] = ['status' => $queueTableExists ? 'healthy' : 'degraded'];
        } catch (\Exception $e) {
            $checks['queue'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
            $healthy = false;
        }

        // App info
        $checks['app'] = [
            'name' => config('app.name'),
            'env' => config('app.env'),
            'version' => '1.0.0',
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];

        return response()->json([
            'success' => $healthy,
            'message' => $healthy ? 'جميع الخدمات تعمل بكفاءة' : 'خلل في بعض الخدمات',
            'data' => [
                'status' => $healthy ? 'healthy' : 'degraded',
                'timestamp' => now()->toIso8601String(),
                'checks' => $checks,
            ],
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-Id'),
        ], $healthy ? 200 : 503);
    });

    Route::post('/csp-report', function () {
        $report = request()->getContent();
        \Illuminate\Support\Facades\Log::warning('CSP Violation', ['report' => $report]);
        return response()->json(['success' => true], 204);
    });
});
