# 02 - العمارة (Architecture)

## نظرة عامة على العمارة

معمارية خريطة الوكلاء (Agent Map) تتيح للمستخدمين تحديد موقع الوكلاء القريبين باستخدام إحداثيات GPS والاستعلام المكاني.

```
          +-----------+      +------------+      +-------------+      +-------------+
          |  المستخدم |      |  API       |      |  MapService |      |  MySQL      |
          | (User)    |      |  Gateway   |      |  Spatial    |      |  (Spatial)  |
          +-----+-----+      +-----+------+      +------+------+      +------+------+
                |                   |                    |                    |
   1.          |                   |                    |                    |
   GPS lat/lng |------------------>|                    |                    |
                |                   |                    |                    |
   2. Query    |                   |------------------->|                    |
   nearby      |                   |                    |                    |
                |                   |                    |                    |
   3. استعلام  |                   |                    |--------------------|
   مكاني      |                   |                    | ST_Distance_Sphere|
                |                   |                    |                    |
   4. Cache    |                   |                    |--------------------|
               |                   |                    | (Redis Cache)      |
                |                   |                    |                    |
   5. الرد مع  |                   |<-------------------|                    |
   الوكلاء     |<------------------|                    |                    |
                |                   |                    |                    |
```

## مكدس الطبقات (Layer Stack)

```
Flutter/React SPA --> API Gateway --> Controller --> AgentMapService --> Database
                                                |
                                                +--> WalletService (quick balance)
                                                +--> Redis Cache
```

## حساب المسافة (Earth Distance Calculation)

نستخدم MySQL `ST_Distance_Sphere` لحساب المسافة بين نقطتين على سطح الكرة الأرضية:

```sql
-- حساب المسافة بالأمتار بين المستخدم والوكيل
SELECT
    id,
    name,
    latitude,
    longitude,
    ROUND(ST_Distance_Sphere(
        POINT(:userLng, :userLat),
        POINT(longitude, latitude)
    )) AS distance_meters
FROM agents
WHERE is_online = 1
    AND ST_Distance_Sphere(
        POINT(:userLng, :userLat),
        POINT(longitude, latitude)
    ) <= :radiusMeters
ORDER BY distance_meters ASC
LIMIT 50;
```

## التخزين المؤقت (Caching)

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AgentMapCacheService
{
    private const int CACHE_TTL = 60; // ثانية

    /**
     * تخزين مؤقت للوكلاء القريبين
     */
    public function getNearbyAgents(float $lat, float $lng, int $radiusKm): ?array
    {
        $cacheKey = "nearby_agents:{$lat}:{$lng}:{$radiusKm}";

        return Cache::get($cacheKey);
    }

    public function setNearbyAgents(float $lat, float $lng, int $radiusKm, array $agents): void
    {
        $cacheKey = "nearby_agents:{$lat}:{$lng}:{$radiusKm}";

        Cache::put($cacheKey, $agents, now()->addSeconds(self::CACHE_TTL));
    }

    /**
     * إبطال التخزين المؤقت عند تغيير حالة الوكيل
     */
    public function invalidateAgentCache(int $agentId): void
    {
        $pattern = "nearby_agents:*";
        // مسح جميع مفاتيح التخزين المؤقت للوكلاء القريبين
        foreach (Cache::get($pattern, []) as $key) {
            Cache::forget($key);
        }
    }
}
```

## Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NearbyAgentsRequest;
use App\Services\AgentMapService;
use Illuminate\Http\JsonResponse;

class AgentMapController extends Controller
{
    public function __construct(
        private readonly AgentMapService $mapService
    ) {}

    public function nearby(NearbyAgentsRequest $request): JsonResponse
    {
        $agents = $this->mapService->findNearbyAgents(
            lat: $request->float('lat'),
            lng: $request->float('lng'),
            radiusKm: $request->integer('radius', 10),
            filters: $request->only(['service_type', 'is_online']),
        );

        return response()->json([
            'success' => true,
            'data' => $agents,
            'meta' => [
                'count' => count($agents),
                'lat' => $request->float('lat'),
                'lng' => $request->float('lng'),
                'radius_km' => $request->integer('radius', 10),
            ],
        ]);
    }
}
```

## مسارات API

```php
// routes/api.php
Route::middleware(['auth:api', 'throttle:30,1'])->group(function () {
    Route::get('/agent/nearby', [AgentMapController::class, 'nearby']);
    Route::post('/agent/update-location', [AgentMapController::class, 'updateLocation']);
    Route::get('/agent/{id}/location', [AgentMapController::class, 'getLocation']);
});
```

## الملفات المرتبطة

| الملف | المسار |
|-------|--------|
| Controller | `app/Http/Controllers/Api/AgentMapController.php` |
| Service | `app/Services/AgentMapService.php` |
| Cache Service | `app/Services/AgentMapCacheService.php` |
| WalletService | `app/Services/WalletService.php` |
| Form Request | `app/Http/Requests/NearbyAgentsRequest.php` |

## توقيت الأداء المستهدف

| الخطوة | الوقت (p95) |
|--------|-------------|
| استلام الطلب | ~5ms |
| تحقق (Auth + Throttle) | ~10ms |
| تحقق من صحة البيانات | ~3ms |
| استعلام مكاني (MySQL Spatial) | ~20ms |
| تخزين مؤقت (Redis) | ~2ms |
| استعلام الرصيد | ~15ms |
| استجابة JSON | ~5ms |
| **المجموع** | **~60ms** |
