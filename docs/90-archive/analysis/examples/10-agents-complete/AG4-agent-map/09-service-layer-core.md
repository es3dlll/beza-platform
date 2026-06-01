# 09 - سيرفس لير العملية (Service Layer)

## AgentMapService

```php
<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\DB;

class AgentMapService
{
    public function findNearby(float $lat, float $lng, float $radius, bool $availableOnly): \Illuminate\Support\Collection
    {
        // معادلة هافرسين (Haversine) لحساب المسافة
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(location_lat))
                        * cos(radians(location_lng) - radians(?))
                        + sin(radians(?)) * sin(radians(location_lat))))";

        $query = Agent::select('*', DB::raw("{$haversine} AS distance_km"))
            ->where('status', 'active')
            ->whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->having('distance_km', '<=', $radius);

        if ($availableOnly) {
            $query->where('available', true);
        }

        return $query
            ->orderBy('distance_km')
            ->limit(50)
            ->setBindings([$lat, $lng, $lat])
            ->get()
            ->map(function ($agent) {
                $agent->distance_km = round($agent->distance_km, 2);
                return $agent;
            });
    }
}
```

## تدفق الخدمة

1. استقبال إحداثيات المستخدم (lat, lng) ونطاق البحث (radius)
2. استخدام معادلة هافرسين لحساب المسافة بين المستخدم وكل وكيل
3. تصفية الوكلاء النشطين فقط ضمن النطاق المطلوب
4. تصفية حسب حالة التوفر (اختياري)
5. ترتيب حسب المسافة (الأقرب أولاً)
6. إرجاع 50 وكيلاً كحد أقصى مع المسافة مقربة إلى منزلتين عشريتين
