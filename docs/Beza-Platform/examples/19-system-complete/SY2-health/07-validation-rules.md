# 07 - قواعد التحقق من صحة المدخلات (Validation Rules)

**الرمز التشغيلي:** SY2-health  
**النوع:** توثيق التحقق (Validation Documentation)

---

## خلاصة (Summary)

عملية SY2-health لا تستقبل أي مدخلات من المستخدم (لا request body, لا parameters). كل نقاط النهاية هي `GET` بدون أي متغيرات. التحقق الوحيد المطلوب هو **التحقق من صلاحية المستخدم** (المصادقة والصلاحية) للوحة المشرف.

---

## التحقق من المصادقة (Authentication Validation)

### المسارات العامة (Public Routes)

```
GET /system/health         ← لا يحتاج مصادقة
GET /system/health/db      ← لا يحتاج مصادقة
GET /system/health/redis   ← لا يحتاج مصادقة
GET /system/health/cache   ← لا يحتاج مصادقة
GET /system/health/queue   ← لا يحتاج مصادقة
GET /system/health/requirements ← لا يحتاج مصادقة
GET /system/health/storage ← لا يحتاج مصادقة
```

**السبب:** أدوات المراقبة الخارجية (Nagios, Prometheus, UptimeRobot) تحتاج نقاط نهاية بدون مصادقة لفحص النظام آلياً.

### مسار المشرف (Admin Route)

```
GET /admin/system/health    ← يتطلب: auth:api + role:admin
```

هذا المسار محمي بميدلوير `auth:api` (JWT) ودور `admin`.

---

## كود التحقق من الصلاحية (Authorization Code)

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Health\HealthService;
use Tymon\JWTAuth\Facades\JWTAuth;

class HealthController extends Controller
{
    protected HealthService $healthService;

    public function __construct(HealthService $healthService)
    {
        // ترجمة: حقن خدمة التحقق الصحي
        $this->healthService = $healthService;
    }

    /**
     * لوحة المشرف - تحتاج صلاحية admin
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminDashboard()
    {
        // ترجمة: التحقق من أن المستخدم الحالي له دور admin
        // يتم هذا عبر middleware: auth:api + role:admin
        // لكن للتأكد نضيف تحقق إضافي هنا
        try {
            // ترجمة: محاولة جلب المستخدم من التوكن
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                // ترجمة: المستخدم غير موجود أو التوكن غير صالح
                return response()->json([
                    'status'  => 'error',
                    'message' => 'المستخدم غير مصرح له بالوصول'
                ], 401);
            }

            if (!$user->hasRole('admin')) {
                // ترجمة: المستخدم ليس لديه صلاحية المشرف
                return response()->json([
                    'status'  => 'error',
                    'message' => 'هذه المعلومات متاحة فقط للمشرفين'
                ], 403);
            }

            // ترجمة: جلب التقرير الصحي المفصل
            $report = $this->healthService->getDetailedReport();

            return response()->json($report);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            // ترجمة: التوكن منتهي الصلاحية
            return response()->json([
                'status'  => 'error',
                'message' => 'انتهت صلاحية التوكن، يرجى تسجيل الدخول مرة أخرى'
            ], 401);

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            // ترجمة: التوكن غير صالح
            return response()->json([
                'status'  => 'error',
                'message' => 'التوكن غير صالح'
            ], 401);

        } catch (\Exception $e) {
            // ترجمة: خطأ غير متوقع
            return response()->json([
                'status'  => 'error',
                'message' => 'خطأ في المصادقة'
            ], 401);
        }
    }
}
```

---

## التحقق من معدل الطلبات (Rate Limiting)

نطبق rate limiting على جميع نقاط النهاية لمنع إساءة الاستخدام:

```php
<?php
// routes/api.php - إعدادات التوجيه

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;

/*
 * ترجمة: المسارات العامة للتحقق الصحي
 * مع تحديد معدل 60 طلب في الدقيقة للفرد
 */
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/system/health',             [HealthController::class, 'index']);
    Route::get('/system/health/db',          [HealthController::class, 'checkDb']);
    Route::get('/system/health/redis',       [HealthController::class, 'checkRedis']);
    Route::get('/system/health/cache',       [HealthController::class, 'checkCache']);
    Route::get('/system/health/queue',       [HealthController::class, 'checkQueue']);
    Route::get('/system/health/requirements',[HealthController::class, 'checkRequirements']);
    Route::get('/system/health/storage',     [HealthController::class, 'checkStorage']);
});

/*
 * ترجمة: مسار المشرف المحمي
 * بمصادقة JWT وصلاحية admin
 */
Route::middleware(['auth:api', 'role:admin', 'throttle:30,1'])->group(function () {
    Route::get('/admin/system/health',       [HealthController::class, 'adminDashboard']);
});
```

---

## أنواع التحقق (Validation Types)

| نوع التحقق (Type) | المسار (Route) | القاعدة (Rule) |
|------------------|---------------|---------------|
| مصادقة (Authentication) | `/admin/system/health` | JWT token صالح |
| صلاحية (Authorization) | `/admin/system/health` | دور المستخدم = admin |
| معدل الطلبات (Rate Limit) | جميع المسارات | 60 طلب/دقيقة للعامة، 30 طلب/دقيقة للمشرف |
| طريقة الطلب (HTTP Method) | جميع المسارات | GET فقط |
| نوع المحتوى (Content-Type) | جميع المسارات | application/json |

---

## ملخص قواعد التحقق (Validation Rules Summary)

```
┌─────────────────────┬─────────────┬────────────────┬──────────────────┐
│       المسار        │  المصادقة   │   الصلاحية     │  معدل الطلبات   │
├─────────────────────┼─────────────┼────────────────┼──────────────────┤
│ /system/health/*    │  غير مطلوبة  │   غير مطلوبة   │  60/دقيقة       │
│ /admin/system/health│  مطلوبة     │  admin فقط     │  30/دقيقة       │
└─────────────────────┴─────────────┴────────────────┴──────────────────┘
```

لا توجد validations إضافية لأن النظام لا يستقبل أي مدخلات من المستخدم.
