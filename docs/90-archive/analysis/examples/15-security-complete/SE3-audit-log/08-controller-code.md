# 08 - متحكم سجل التدقيق (Controller Code)

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'admin']);
    }

    /**
     * GET /api/v1/admin/audit-logs
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');

        // تصفية حسب نوع الحدث
        if ($request->event_type) {
            $query->where('event_type', $request->event_type);
        }

        // تصفية حسب المستخدم
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // تصفية حسب التاريخ
        if ($request->from) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->to) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * GET /api/v1/admin/audit-logs/{id}
     */
    public function show(int $id): JsonResponse
    {
        $log = AuditLog::with('user', 'loggable')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    /**
     * GET /api/v1/admin/audit-logs/events — قائمة أنواع الأحداث
     */
    public function eventTypes(): JsonResponse
    {
        $types = AuditLog::select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type');

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * GET /api/v1/admin/audit-logs/stats
     */
    public function stats(): JsonResponse
    {
        $today = AuditLog::whereDate('created_at', today())->count();
        $thisWeek = AuditLog::whereBetween('created_at', [now()->startOfWeek(), now()])->count();
        $total = AuditLog::count();

        $byEvent = AuditLog::selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'today' => $today,
                'this_week' => $thisWeek,
                'total' => $total,
                'top_events' => $byEvent,
            ],
        ]);
    }
}
```
