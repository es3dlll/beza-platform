# 08 - الكود الكامل للـ HealthController (HealthController Full Code)

**الرمز التشغيلي:** SY2-health  
**النوع:** كود كامل (Full Code)

---

## الكود الكامل (Full Code)

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Health\HealthService;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * كنترولر التحقق الصحي للمنصة
 * 
 * يوفر نقاط نهاية لفحص جميع الخدمات الحيوية
 * وإرجاع تقرير شامل عن حالتها
 */
class HealthController extends Controller
{
    /**
     * خدمة التحقق الصحي
     *
     * @var HealthService
     */
    protected HealthService $healthService;

    /**
     * إنشاء الكنترولر مع حقن التبعيات
     *
     * @param HealthService $healthService
     */
    public function __construct(HealthService $healthService)
    {
        // ترجمة: حقن خدمة التحقق الصحي عبر الـ constructor
        $this->healthService = $healthService;
    }

    /**
     * التحقق الصحي العام - فحص جميع الخدمات
     *
     * @return JsonResponse
     * 
     * @api GET /system/health
     */
    public function index(): JsonResponse
    {
        // ترجمة: جلب التقرير الصحي العام (مخزن مؤقت لمدة 30 ثانية)
        $report = $this->healthService->getGeneralHealthReport();

        return response()->json($report);
    }

    /**
     * فحص قاعدة البيانات - اختبار اتصال MySQL
     *
     * @return JsonResponse
     * 
     * @api GET /system/health/db
     */
    public function checkDb(): JsonResponse
    {
        // ترجمة: تشغيل فحص قاعدة البيانات والحصول على النتيجة
        $result = $this->healthService->checkDatabase();

        return response()->json([
            'status'    => $result->status,
            'service'   => 'database',
            'latency_ms'=> $result->latency_ms,
            'timestamp' => now()->toIso8601String(),
            'error'     => $result->error,
        ]);
    }

    /**
     * فحص Redis - اختبار الاتصال والـ ping
     *
     * @return JsonResponse
     * 
     * @api GET /system/health/redis
     */
    public function checkRedis(): JsonResponse
    {
        // ترجمة: تشغيل فحص Redis
        $result = $this->healthService->checkRedis();

        return response()->json([
            'status'    => $result->status,
            'service'   => 'redis',
            'latency_ms'=> $result->latency_ms,
            'timestamp' => now()->toIso8601String(),
            'error'     => $result->error,
        ]);
    }

    /**
     * فحص الذاكرة المؤقتة - اختبار كتابة وقراءة
     *
     * @return JsonResponse
     * 
     * @api GET /system/health/cache
     */
    public function checkCache(): JsonResponse
    {
        // ترجمة: تشغيل فحص الكاش
        $result = $this->healthService->checkCache();

        return response()->json([
            'status'    => $result->status,
            'service'   => 'cache',
            'latency_ms'=> $result->latency_ms,
            'timestamp' => now()->toIso8601String(),
            'error'     => $result->error,
        ]);
    }

    /**
     * فحص قائمة الانتظار - اختبار الاتصال
     *
     * @return JsonResponse
     * 
     * @api GET /system/health/queue
     */
    public function checkQueue(): JsonResponse
    {
        // ترجمة: تشغيل فحص قائمة الانتظار
        $result = $this->healthService->checkQueue();

        return response()->json([
            'status'    => $result->status,
            'service'   => 'queue',
            'latency_ms'=> $result->latency_ms,
            'timestamp' => now()->toIso8601String(),
            'driver'    => $result->details['driver'] ?? config('queue.default'),
            'error'     => $result->error,
        ]);
    }

    /**
     * فحص متطلبات PHP - الإضافات والإصدارات
     *
     * @return JsonResponse
     * 
     * @api GET /system/health/requirements
     */
    public function checkRequirements(): JsonResponse
    {
        // ترجمة: تشغيل فحص متطلبات PHP
        $result = $this->healthService->checkRequirements();

        return response()->json([
            'status'      => $result->status,
            'service'     => 'php_requirements',
            'php_version' => $result->details['php_version'] ?? PHP_VERSION,
            'extensions'  => $result->details['extensions'] ?? [],
            'timestamp'   => now()->toIso8601String(),
            'error'       => $result->error,
        ]);
    }

    /**
     * فحص التخزين - صلاحيات الكتابة للمجلدات
     *
     * @return JsonResponse
     * 
     * @api GET /system/health/storage
     */
    public function checkStorage(): JsonResponse
    {
        // ترجمة: تشغيل فحص التخزين
        $result = $this->healthService->checkStorage();

        return response()->json([
            'status'       => $result->status,
            'service'      => 'storage',
            'directories'  => $result->details['directories'] ?? [],
            'disk_usage'   => $result->details['disk_usage'] ?? null,
            'timestamp'    => now()->toIso8601String(),
            'error'        => $result->error,
        ]);
    }

    /**
     * لوحة المشرف - تقرير مفصل مع معلومات إضافية
     * 
     * يتطلب صلاحية admin ومصادقة JWT
     *
     * @return JsonResponse
     * 
     * @api GET /admin/system/health
     */
    public function adminDashboard(): JsonResponse
    {
        // ترجمة: التحقق من هوية المستخدم عبر JWT
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                // ترجمة: المستخدم غير موجود أو التوكن غير صالح
                return response()->json([
                    'status'  => 'error',
                    'message' => 'غير مصرح بالوصول'
                ], 401);
            }

            if (!$user->hasRole('admin')) {
                // ترجمة: المستخدم ليس مشرفاً
                return response()->json([
                    'status'  => 'error',
                    'message' => 'هذه اللوحة متاحة للمشرفين فقط'
                ], 403);
            }

            // ترجمة: جلب التقرير المفصل (يشمل معلومات النظام الكاملة)
            $report = $this->healthService->getDetailedReport();

            return response()->json($report);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            // ترجمة: التوكن منتهي الصلاحية
            return response()->json([
                'status'  => 'error',
                'message' => 'انتهت صلاحية الجلسة، يرجى تسجيل الدخول مجدداً'
            ], 401);

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            // ترجمة: التوكن غير صالح
            return response()->json([
                'status'  => 'error',
                'message' => 'التوكن غير صالح'
            ], 401);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // ترجمة: خطأ عام في JWT
            return response()->json([
                'status'  => 'error',
                'message' => 'مطلوب توكن للمصادقة'
            ], 401);
        }
    }
}
```

---

## ملخص دوال الكنترولر (Controller Methods Summary)

| الدالة (Method) | المسار (Route) | الوظيفة (Function) |
|----------------|---------------|-------------------|
| `index()` | `GET /system/health` | فحص جميع الخدمات، مع تخزين مؤقت 30 ثانية |
| `checkDb()` | `GET /system/health/db` | فحص MySQL فقط |
| `checkRedis()` | `GET /system/health/redis` | فحص Redis فقط |
| `checkCache()` | `GET /system/health/cache` | فحص الكاش فقط |
| `checkQueue()` | `GET /system/health/queue` | فحص قائمة الانتظار فقط |
| `checkRequirements()` | `GET /system/health/requirements` | فحص PHP فقط |
| `checkStorage()` | `GET /system/health/storage` | فحص التخزين فقط |
| `adminDashboard()` | `GET /admin/system/health` | تقرير مفصل مع JWT + صلاحية admin |
