<?php

declare(strict_types=1);

namespace App\Modules\AuditLog\Controllers;

use App\Modules\AuditLog\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditLogController
{
    private const SENSITIVE_FIELDS = ['password', 'token', 'secret', 'authorization'];

    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query();

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('result')) {
            $query->where('result', $request->input('result'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $logs->through(function (AuditLog $log): array {
            $data = $log->toArray();

            if (isset($data['metadata']) && is_array($data['metadata'])) {
                $data['metadata'] = $this->maskSensitive($data['metadata']);
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب سجل التدقيق',
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => $request->header('X-Request-Id'),
        ]);
    }

    private function maskSensitive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_FIELDS, true)) {
                $data[$key] = '***';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitive($value);
            }
        }

        return $data;
    }
}
