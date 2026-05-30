<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Support\ApiResponse;

final class HealthController extends Controller
{
    use ApiResponse;
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];
        $status = collect($checks)->contains(fn ($c) => $c['status'] === 'error') ? 503 : 200;

        return $this->respondWithMeta(
            data: [
                'status' => $status === 200 ? 'healthy' : 'degraded',
                'timestamp' => now()->toIso8601String(),
                'app' => config('app.name'),
                'env' => config('app.env'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
            meta: ['checks' => $checks],
            status: $status,
        );
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
            return ['status' => 'ok', 'message' => 'Storage reachable'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $queue = config('queue.default');
            Cache::store(config('cache.default'))->put('queue-health', time(), 1);
            Cache::store(config('cache.default'))->forget('queue-health');
            return ['status' => 'ok', 'message' => "Queue driver: {$queue}"];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
