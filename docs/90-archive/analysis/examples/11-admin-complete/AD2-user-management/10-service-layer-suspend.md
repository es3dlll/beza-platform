# 10 - UserSuspendService (إدارة التعليق)

```php
<?php
// app/Services/Admin/UserSuspendService.php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserSuspendService
{
    /**
     * تعليق الحساب — يمنع تسجيل الدخول فوراً
     */
    public function suspend(User $user, string $reason = null): void
    {
        $user->update([
            'status' => 'suspended',
        ]);

        // إبطال جميع توكنات JWT
        JWTAuth::invalidate(true);

        // تخزين سبب التعليق في Cache (لظهوره عند محاولة الدخول)
        if ($reason) {
            Cache::put("suspend_reason:{$user->id}", $reason, now()->addDays(30));
        }

        Log::info("User account suspended", [
            'user_id' => $user->id,
            'reason'  => $reason,
        ]);
    }

    /**
     * تفعيل الحساب
     */
    public function activate(User $user): void
    {
        $user->update([
            'status' => 'active',
        ]);

        Cache::forget("suspend_reason:{$user->id}");

        Log::info("User account activated", [
            'user_id' => $user->id,
        ]);
    }

    /**
     * حظر نهائي
     */
    public function block(User $user): void
    {
        $user->update([
            'status' => 'blocked',
        ]);

        // إبطال جميع التوكنات
        JWTAuth::invalidate(true);

        Log::warning("User account blocked permanently", [
            'user_id' => $user->id,
        ]);
    }

    /**
     * التحقق من حالة المستخدم عند تسجيل الدخول
     */
    public function checkLoginAbility(User $user): ?string
    {
        if ($user->deleted_at) {
            return 'هذا الحساب محذوف';
        }

        if ($user->status === 'suspended') {
            $reason = Cache::get("suspend_reason:{$user->id}");
            return $reason ? "حسابك معلق: {$reason}" : 'حسابك موقوف مؤقتاً';
        }

        if ($user->status === 'blocked') {
            return 'تم حظر حسابك بشكل دائم';
        }

        return null; // مسموح بالدخول
    }

    /**
     * حذف ناعم مع الاحتفاظ بالعلاقات
     */
    public function softDelete(User $user): void
    {
        // المعاملات ستبقى مع SET NULL
        // المحافظ ستحذف CASCADE
        // لكننا نريد الحفاظ على المحافظ للرجوع إليها

        $user->wallets()->update(['is_active' => false]);

        $user->delete(); // soft delete

        Log::info("User soft deleted", [
            'user_id' => $user->id,
        ]);
    }
}
```

## Auth Middleware — التحقق من الحالة

```php
<?php
// app/Http/Middleware/CheckUserStatus.php

namespace App\Http\Middleware;

use App\Services\Admin\UserSuspendService;
use Closure;
use Illuminate\Http\Request;

class CheckUserStatus
{
    public function __construct(
        private readonly UserSuspendService $suspendService
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()) {
            $error = $this->suspendService->checkLoginAbility($request->user());

            if ($error) {
                JWTAuth::invalidate(true);

                return response()->json([
                    'success' => false,
                    'message' => $error,
                ], 403);
            }
        }

        return $next($request);
    }
}
```

## تسجيل middleware في Kernel

```php
// app/Http/Kernel.php
protected $routeMiddleware = [
    'user.active' => \App\Http\Middleware\CheckUserStatus::class,
];
```
