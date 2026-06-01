# 08 - متحكم تقارير الاحتيال (Controller Code)

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlaggedTransaction;
use App\Models\BlockedIp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FraudReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('admin');
    }

    /**
     * GET /api/v1/admin/fraud/report
     */
    public function index(Request $request): JsonResponse
    {
        $query = FlaggedTransaction::with(['user', 'transaction', 'reviewer'])
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->to) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $flagged = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $flagged,
        ]);
    }

    /**
     * POST /api/v1/admin/fraud/{id}/approve
     */
    public function approve(int $id): JsonResponse
    {
        $flagged = FlaggedTransaction::findOrFail($id);
        $flagged->approve(auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على المعاملة',
        ]);
    }

    /**
     * POST /api/v1/admin/fraud/{id}/reject
     */
    public function reject(int $id, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $flagged = FlaggedTransaction::findOrFail($id);
        $flagged->reject(auth()->user(), $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض المعاملة',
        ]);
    }

    /**
     * GET /api/v1/admin/fraud/stats
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'pending' => FlaggedTransaction::where('status', 'pending')->count(),
                'approved' => FlaggedTransaction::where('status', 'approved')->count(),
                'rejected' => FlaggedTransaction::where('status', 'rejected')->count(),
                'total' => FlaggedTransaction::count(),
                'high_risk' => FlaggedTransaction::where('risk_score', '>=', 70)
                    ->where('status', 'pending')->count(),
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/blocked-ips
     */
    public function blockIp(Request $request): JsonResponse
    {
        $request->validate([
            'ip' => 'required|ip',
            'reason' => 'required|string|max:255',
        ]);

        BlockedIp::create([
            'ip' => $request->ip,
            'reason' => $request->reason,
            'blocked_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حظر IP بنجاح',
        ]);
    }
}
```
