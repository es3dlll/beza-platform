<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $status = 200;
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $failed = collect($checks)->first(fn ($c) => $c['status'] !== 'ok');

        if ($failed) {
            $status = 503;
        }

        return response()->json([
            'success' => $status === 200,
            'data' => [
                'status' => $status === 200 ? 'healthy' : 'degraded',
                'timestamp' => now()->toIso8601String(),
                'app' => config('app.name'),
                'env' => config('app.env'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
            'meta' => [
                'checks' => $checks,
            ],
        ], $status);
    }

    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'ok', 'message' => 'Database connection established'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::store(config('cache.default'))->get('health-check');
            return ['status' => 'ok', 'message' => 'Cache reachable'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        try {
            Storage::disk('local')->exists('health-check');
            return ['status' => 'ok', 'message' => 'Storage writable'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
