# 19 - الحالات الحدودية (Edge Cases)

## 1. لا يوجد وكلاء ضمن نصف القطر (No Agents Within Radius)

**المشكلة**: المستخدم يبحث ضمن نصف قطر صغير جداً ولا يوجد وكلاء.

**الحل**:
```php
<?php

use App\Exceptions\NoAgentsNearbyException;

public function findWithFallback(float $lat, float $lng, int $radiusKm): array
{
    $agents = $this->spatialQuery($lat, $lng, $radiusKm);

    if ($agents->isEmpty()) {
        // محاولة مع نصف قطر أكبر
        $expandedRadius = $radiusKm * 2;
        $agents = $this->spatialQuery($lat, $lng, $expandedRadius);

        if ($agents->isEmpty()) {
            throw new NoAgentsNearbyException(
                sprintf(
                    'لا يوجد وكلاء ضمن %d كم. جرب البحث في مدينة قريبة.',
                    $radiusKm
                ),
                suggestedRadius: $expandedRadius
            );
        }

        // إرجاع النتائج مع تحذير
        return [
            'agents' => $this->formatResponse($agents),
            'warning' => "تم توسيع نطاق البحث إلى {$expandedRadius} كم.",
        ];
    }

    return ['agents' => $this->formatResponse($agents)];
}
```

## 2. عدم دقة GPS (GPS Inaccuracies)

**المشكلة**: إحداثيات GPS غير دقيقة بسبب ضعف الإشارة أو تداخل.

**الحل**:
```php
<?php

public function snapToGrid(float $lat, float $lng): array
{
    // تقريب الإحداثيات لأقرب 3 منازل عشرية (~111m دقة)
    $snappedLat = round($lat, 3);
    $snappedLng = round($lng, 3);

    return [$snappedLat, $snappedLng];
}

public function validateAccuracy(?float $accuracy): void
{
    if ($accuracy !== null && $accuracy > 500) {
        // دقة منخفضة - استخدام موقع آخر معروف
        Log::warning("دقة GPS منخفضة: {$accuracy}m");
    }
}

public function getLastKnownLocation(int $agentId): ?array
{
    return AgentLocation::where('agent_id', $agentId)
        ->where('created_at', '>=', now()->subHours(1))
        ->orderByDesc('created_at')
        ->first()
        ?->only(['latitude', 'longitude', 'accuracy']);
}

public function updateLocationWithValidation(int $agentId, float $lat, float $lng, ?float $accuracy): void
{
    $this->validateAccuracy($accuracy);

    [$snappedLat, $snappedLng] = $this->snapToGrid($lat, $lng);

    // إذا كانت الدقة منخفضة جداً، استخدم آخر موقع معروف
    if ($accuracy !== null && $accuracy > 1000) {
        $lastLocation = $this->getLastKnownLocation($agentId);
        if ($lastLocation) {
            [$snappedLat, $snappedLng] = [
                $lastLocation['latitude'],
                $lastLocation['longitude'],
            ];
        }
    }

    $this->updateAgentLocation($agentId, $snappedLat, $snappedLng, $accuracy);
}
```

## 3. وكيل يغادر أثناء البحث (Agent Goes Offline Mid-Search)

**المشكلة**: الوكيل كان متاحاً عند بدء البحث لكنه أصبح غير متاح أثناء عرض النتائج.

**الحل**:
```php
<?php

public function findAndVerifyAvailability(float $lat, float $lng, int $radiusKm): array
{
    $agents = $this->spatialQuery($lat, $lng, $radiusKm);

    // تصفية الوكلاء الذين أصبحوا غير متاحين
    $availableAgents = $agents->filter(function ($agent) {
        if (!$agent->is_online) {
            return false;
        }

        // التحقق من آخر ظهور (خلال آخر 5 دقائق)
        if ($agent->last_seen_at && $agent->last_seen_at->lt(now()->subMinutes(5))) {
            return false;
        }

        return true;
    });

    return [
        'agents' => $this->formatResponse($availableAgents),
        'meta' => [
            'total_found' => $agents->count(),
            'available_now' => $availableAgents->count(),
            'query_time' => now(),
        ],
    ];
}

/**
 * WebSocket event: real-time availability update
 */
public function broadcastAvailabilityChange(int $agentId, bool $isOnline): void
{
    broadcast(new AgentAvailabilityChanged($agentId, $isOnline))->toOthers();
}
```

## 4. مناطق كثيفة جداً (Very Dense Areas - Limit Results)

**المشكلة**: في المدن الكبيرة (دمشق، حلب)، عدد الوكلاء كبير جداً.

**الحل**:
```php
<?php

public function findWithDensityControl(float $lat, float $lng, int $radiusKm, int $limit = 50): array
{
    $totalCount = $this->countAgentsInRadius($lat, $lng, $radiusKm);

    // تحديد الحد حسب الكثافة
    $adjustedLimit = match (true) {
        $totalCount > 200 => min($limit, 20),   // كثافة عالية جداً
        $totalCount > 100 => min($limit, 30),   // كثافة عالية
        $totalCount > 50  => min($limit, 50),   // كثافة متوسطة
        default           => $limit,             // كثافة منخفضة
    };

    $agents = $this->spatialQuery($lat, $lng, $radiusKm, limit: $adjustedLimit);

    return [
        'agents' => $this->formatResponse($agents),
        'meta' => [
            'total_in_area' => $totalCount,
            'returned' => $agents->count(),
            'density' => match (true) {
                $totalCount > 200 => 'very_high',
                $totalCount > 100 => 'high',
                $totalCount > 50 => 'medium',
                default => 'low',
            },
            'radius_km' => $radiusKm,
        ],
    ];
}

private function countAgentsInRadius(float $lat, float $lng, int $radiusKm): int
{
    $radiusMeters = $radiusKm * 1000;

    return User::where('role', 'agent')
        ->where('is_online', true)
        ->whereRaw("ST_Distance_Sphere(
            POINT({$lng}, {$lat}),
            POINT(longitude, latitude)
        ) <= {$radiusMeters}")
        ->count();
}
```

## 5. تحديث الموقع المتكرر (Frequent Location Updates)

**المشكلة**: الوكيل يحدث موقعه كل ثانية مما يسبب ضغطاً على الخادم.

**الحل**:
```php
<?php

public function shouldUpdateLocation(int $agentId, float $newLat, float $newLng): bool
{
    $lastUpdate = Cache::get("last_location_update:{$agentId}");

    if ($lastUpdate) {
        // منع التحديث إذا كان آخر تحديث قبل أقل من 30 ثانية
        if ($lastUpdate > now()->subSeconds(30)) {
            return false;
        }
    }

    // منع التحديث إذا كان الموقع لم يتغير كثيراً (< 50m)
    $lastLocation = User::find($agentId)?->only(['latitude', 'longitude']);

    if ($lastLocation && $lastLocation['latitude'] && $lastLocation['longitude']) {
        $distance = $this->haversineDistance(
            $lastLocation['latitude'], $lastLocation['longitude'],
            $newLat, $newLng
        );

        if ($distance < 50) { // أقل من 50 متر
            return false;
        }
    }

    return true;
}

private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371000; // متر

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) ** 2 +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) ** 2;

    return $earthRadius * 2 * asin(sqrt($a));
}
```

## ملخص الحالات الحدودية

| الحالة | المشكلة | الحل |
|--------|---------|------|
| لا وكلاء ضمن نصف القطر | نتائج فارغة | توسيع نصف القطر تلقائياً |
| عدم دقة GPS | إحداثيات غير دقيقة | snap to grid + آخر موقع معروف |
| وكيل يغادر أثناء البحث | بيانات قديمة | تحقق real-time + WebSocket |
| مناطق كثيفة جداً | نتائج كثيرة | تحديد النتائج حسب الكثافة |
| تحديث متكرر للموقع | ضغط على الخادم | throttle + حد أدنى للمسافة |
