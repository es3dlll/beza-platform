<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WapRoute;
use Illuminate\Http\Request;

class WapManagementController extends Controller
{
    public function devices(): \Illuminate\Http\JsonResponse
    {
        $devices = AuditLog::selectRaw('fingerprint, user_agent, COUNT(*) as request_count, MAX(created_at) as last_seen')
            ->where('fingerprint', '!=', '')
            ->groupBy('fingerprint', 'user_agent')
            ->orderByDesc('last_seen')
            ->limit(50)
            ->get()
            ->map(fn ($log) => [
                'fingerprint' => $log->fingerprint,
                'user_agent' => $log->user_agent,
                'request_count' => (int) $log->request_count,
                'last_seen' => $log->last_seen,
            ]);

        return response()->json(['success' => true, 'data' => $devices]);
    }

    public function queue(): \Illuminate\Http\JsonResponse
    {
        $pending = Transaction::where('status', 'pending')->count();
        $completed = Transaction::where('status', 'completed')->count();
        $failed = Transaction::where('status', 'failed')->count();

        $recent = Transaction::with('user:id,name')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'user' => $t->user?->name ?? '—',
                'amount' => $t->amount,
                'currency' => $t->currency,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'counts' => compact('pending', 'completed', 'failed'),
                'recent' => $recent,
            ],
        ]);
    }

    public function routes(): \Illuminate\Http\JsonResponse
    {
        $routes = WapRoute::orderBy('priority')->get()->map(fn ($r) => [
            'id' => $r->id,
            'method' => $r->method,
            'pattern' => $r->pattern,
            'target' => $r->target,
            'roles' => $r->roles,
            'priority' => $r->priority,
            'is_active' => $r->is_active,
        ]);

        return response()->json(['success' => true, 'data' => $routes]);
    }

    public function updateRoute(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $route = WapRoute::findOrFail($id);

        $validated = $request->validate([
            'method' => 'sometimes|in:GET,POST,PUT,DELETE,PATCH',
            'pattern' => 'sometimes|string|max:255',
            'target' => 'sometimes|string|max:255',
            'roles' => 'sometimes|array',
            'roles.*' => 'string|in:user,merchant,agent,admin',
            'priority' => 'sometimes|integer|min:0|max:999',
            'is_active' => 'sometimes|boolean',
        ]);

        $route->update($validated);

        return response()->json(['success' => true, 'data' => $route->fresh()]);
    }

    public function summary(): \Illuminate\Http\JsonResponse
    {
        $totalUsers = User::count();
        $wapUsers = User::whereIn('role', ['user', 'merchant', 'agent'])->count();
        $totalTransactions = Transaction::count();
        $pendingTransactions = Transaction::where('status', 'pending')->count();
        $totalDevices = AuditLog::where('fingerprint', '!=', '')->distinct('fingerprint')->count('fingerprint');

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'wap_users' => $wapUsers,
                'total_transactions' => $totalTransactions,
                'pending_transactions' => $pendingTransactions,
                'total_devices' => $totalDevices,
            ],
        ]);
    }
}
