# 10 - AdminDealService كامل (مشترك)

_(أنظر 09-service-layer-deal.md — نفس الملف. هذا الملف يمثل الواجهة المشتركة لخدمات Admin)_

## ملخص خدمات Admin للصفقات

| الخدمة | العملية | API |
|--------|---------|-----|
| AdminDealService::create() | إنشاء صفقة جديدة | POST /admin/deals |
| AdminDealService::activate() | تفعيل صفقة (pending → active) | POST /admin/deals/{id}/activate |
| ProfitDistributionService | إتمام + توزيع أرباح | POST /admin/deals/{id}/complete |
| RefundService | إلغاء + استرجاع | POST /admin/deals/{id}/cancel |

## Admin middleware

```php
<?php
// app/Http/Middleware/IsAdmin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()?->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح — هذه العملية تتطلب صلاحيات Admin',
            ], 403);
        }

        return $next($request);
    }
}
```

## تسجيل Middleware في Kernel

```php
// app/Http/Kernel.php
protected $routeMiddleware = [
    // ...
    'is_admin' => \App\Http\Middleware\IsAdmin::class,
];
```
