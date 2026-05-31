<?php

declare(strict_types=1);

namespace App\Modules\Notification\Controllers;

use App\Modules\Notification\Models\NotificationMessage;
use App\Modules\Notification\Services\NotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = NotificationMessage::query();
        if ($u = $request->get('user_id')) $query->where('user_id', $u);
        if ($t = $request->get('type')) $query->byType($t);
        if ($c = $request->get('channel')) $query->byChannel($c);
        if ($s = $request->get('status')) $query->where('status', $s);
        if ($request->boolean('unread')) $query->unread();

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate(20),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->get('user_id');
        $count = NotificationMessage::when($userId, fn($q) => $q->where('user_id', $userId))
            ->unread()->count();
        return response()->json(['data' => ['unread_count' => $count]]);
    }

    public function markRead(string $id): JsonResponse
    {
        $msg = NotificationMessage::find($id);
        if (!$msg) return response()->json(['error' => 'الإشعار غير موجود'], 404);
        $msg->markAsRead();
        return response()->json(['data' => $msg->fresh()]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $userId = $request->get('user_id');
        $updated = NotificationMessage::when($userId, fn($q) => $q->where('user_id', $userId))
            ->unread()->update(['read_at' => now(), 'status' => 'read']);
        return response()->json(['data' => ['updated' => $updated]]);
    }

    public function destroy(string $id): JsonResponse
    {
        $msg = NotificationMessage::find($id);
        if (!$msg) return response()->json(['error' => 'الإشعار غير موجود'], 404);
        $msg->delete();
        return response()->json(['data' => ['deleted' => true]]);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|string',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'channel' => 'nullable|string|in:in_app,email,sms',
            'data' => 'nullable|array',
        ]);

        try {
            $msg = $this->dispatcher->send(
                userId: $validated['user_id'],
                type: $validated['type'],
                title: $validated['title'],
                body: $validated['body'],
                channel: $validated['channel'] ?? 'in_app',
                data: $validated['data'] ?? null,
            );
            return response()->json(['data' => $msg], 201);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function sendBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'required|string',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'channel' => 'nullable|string|in:in_app,email,sms',
        ]);

        $sent = $this->dispatcher->sendBulk(
            recipients: $validated['user_ids'],
            type: $validated['type'],
            title: $validated['title'],
            body: $validated['body'],
            channel: $validated['channel'] ?? 'in_app',
        );
        return response()->json(['data' => ['sent_count' => $sent]], 201);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total' => NotificationMessage::count(),
                'sent' => NotificationMessage::where('status', 'sent')->count(),
                'failed' => NotificationMessage::where('status', 'failed')->count(),
                'unread' => NotificationMessage::unread()->count(),
            ],
        ]);
    }
}
