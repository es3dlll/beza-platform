# 08 - كود المتحكم (Controller)

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendNotificationRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'total' => $notifications->total(),
                'unread_count' => $request->user()
                    ->notifications()
                    ->whereNull('read_at')
                    ->count(),
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
            ],
        ]);
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        $notification->markAsRead();
        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الإشعار كمقروء',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'status' => 'read']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد جميع الإشعارات كمقروءة',
        ]);
    }

    public function send(SendNotificationRequest $request): JsonResponse
    {
        $user = User::findOrFail($request->user_id);
        $this->notificationService->send(
            $user,
            $request->type,
            $request->data ?? [],
            $request->channels ?? null,
            $request->priority ?? 0
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار',
        ], 201);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'total' => $user->notifications()->count(),
                'unread' => $user->notifications()->whereNull('read_at')->count(),
                'by_type' => $user->notifications()
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get(),
            ],
        ]);
    }
}
```
