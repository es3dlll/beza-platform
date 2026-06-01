# 10 - طبقة الخدمة: WalletService للخرائط

## WalletService (استعلام سريع للرصيد)

```php
<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class WalletService
{
    private const int BALANCE_CACHE_TTL = 30; // ثانية

    /**
     * الحصول على رصيد سريع لعرضه بجانب الوكيل على الخريطة
     */
    public function getQuickBalance(int $agentId, string $currency = 'SYP'): array
    {
        $cacheKey = "agent_balance:{$agentId}:{$currency}";

        return Cache::remember($cacheKey, self::BALANCE_CACHE_TTL, function () use ($agentId, $currency) {
            $wallet = Wallet::where('user_id', $agentId)
                ->where('currency', $currency)
                ->first();

            if (!$wallet) {
                return [
                    'agent_id' => $agentId,
                    'currency' => $currency,
                    'balance' => 0,
                    'frozen_balance' => 0,
                    'available' => 0,
                ];
            }

            return [
                'agent_id' => $agentId,
                'currency' => $currency,
                'balance' => (float) $wallet->balance,
                'frozen_balance' => (float) $wallet->frozen_balance,
                'available' => (float) ($wallet->balance - $wallet->frozen_balance),
                'updated_at' => $wallet->updated_at,
            ];
        });
    }

    /**
     * الحصول على أرصدة مجموعة من الوكلاء (للعرض على الخريطة)
     */
    public function getBalancesForNearbyAgents(array $agentIds): Collection
    {
        $balanceMap = collect();

        foreach ($agentIds as $agentId) {
            $balanceMap->put($agentId, [
                'syp' => $this->getQuickBalance($agentId, 'SYP'),
                'usd' => $this->getQuickBalance($agentId, 'USD'),
            ]);
        }

        return $balanceMap;
    }

    /**
     * مسح التخزين المؤقت للرصيد عند التحديث
     */
    public function clearBalanceCache(int $agentId): void
    {
        Cache::forget("agent_balance:{$agentId}:SYP");
        Cache::forget("agent_balance:{$agentId}:USD");
    }
}
```

## AgentMapService (دمج الخريطة مع الرصيد)

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Exceptions\NoAgentsNearbyException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AgentMapService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly AgentMapCacheService $cacheService,
    ) {}

    /**
     * البحث عن الوكلاء القريبين مع الرصيد
     */
    public function findNearbyAgents(
        float $lat,
        float $lng,
        int $radiusKm,
        array $filters = [],
        int $limit = 50,
    ): array {
        // التحقق من التخزين المؤقت
        $cached = $this->cacheService->getNearbyAgents($lat, $lng, $radiusKm);
        if ($cached !== null) {
            return $cached;
        }

        // الاستعلام المكاني
        $agents = $this->spatialQuery($lat, $lng, $radiusKm, $filters, $limit);

        if ($agents->isEmpty()) {
            throw new NoAgentsNearbyException(
                'لا يوجد وكلاء متاحون ضمن نصف القطر المحدد. جرب زيادة نصف القطر.'
            );
        }

        // إضافة الرصيد لكل وكيل
        $agentIds = $agents->pluck('id')->toArray();
        $balances = $this->walletService->getBalancesForNearbyAgents($agentIds);

        $result = $agents->map(function ($agent) use ($balances) {
            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'phone' => $agent->phone,
                'latitude' => (float) $agent->latitude,
                'longitude' => (float) $agent->longitude,
                'distance_meters' => (int) $agent->distance_meters,
                'is_online' => (bool) $agent->is_online,
                'last_seen_at' => $agent->last_seen_at,
                'service_types' => $agent->service_types,
                'rating' => $agent->rating ?? 0,
                'wallet' => $balances->get($agent->id, [
                    'syp' => ['available' => 0],
                    'usd' => ['available' => 0],
                ]),
            ];
        })->toArray();

        // تخزين في الذاكرة المؤقتة
        $this->cacheService->setNearbyAgents($lat, $lng, $radiusKm, $result);

        return $result;
    }

    /**
     * الاستعلام المكاني باستخدام ST_Distance_Sphere
     */
    private function spatialQuery(
        float $lat,
        float $lng,
        int $radiusKm,
        array $filters,
        int $limit
    ): Collection {
        $radiusMeters = $radiusKm * 1000;

        $query = User::select([
            'id',
            'name',
            'phone',
            'latitude',
            'longitude',
            'is_online',
            'last_seen_at',
            'rating',
            DB::raw("ROUND(ST_Distance_Sphere(
                POINT({$lng}, {$lat}),
                POINT(longitude, latitude)
            )) AS distance_meters"),
        ])
        ->where('role', 'agent')
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->whereRaw("ST_Distance_Sphere(
            POINT({$lng}, {$lat}),
            POINT(longitude, latitude)
        ) <= {$radiusMeters}");

        // تطبيق الفلاتر
        if (isset($filters['is_online'])) {
            $query->where('is_online', $filters['is_online']);
        }

        if (!empty($filters['service_type']) && $filters['service_type'] !== 'all') {
            $query->whereJsonContains('service_types', $filters['service_type']);
        }

        return $query->orderBy('distance_meters')
            ->limit($limit)
            ->get();
    }

    /**
     * تحديث موقع الوكيل
     */
    public function updateAgentLocation(int $agentId, float $lat, float $lng, ?float $accuracy = null): void
    {
        DB::transaction(function () use ($agentId, $lat, $lng, $accuracy) {
            $agent = User::findOrFail($agentId);

            $agent->update([
                'latitude' => $lat,
                'longitude' => $lng,
                'location_updated_at' => now(),
                'is_online' => true,
                'last_seen_at' => now(),
            ]);

            // تسجيل الموقع في جدول السجل
            $agent->locations()->create([
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => $accuracy,
                'source' => 'gps',
            ]);
        });

        // إبطال التخزين المؤقت
        $this->cacheService->invalidateAgentCache($agentId);
    }
}
```

## مثال على الاستجابة

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "أحمد محمد",
            "phone": "963944123456",
            "latitude": 33.5138,
            "longitude": 36.2765,
            "distance_meters": 850,
            "is_online": true,
            "last_seen_at": "2024-06-15T10:30:00Z",
            "service_types": ["cash_in", "cash_out"],
            "rating": 4.5,
            "wallet": {
                "syp": {
                    "agent_id": 1,
                    "currency": "SYP",
                    "balance": 500000,
                    "frozen_balance": 100000,
                    "available": 400000
                },
                "usd": {
                    "agent_id": 1,
                    "currency": "USD",
                    "balance": 500,
                    "frozen_balance": 0,
                    "available": 500
                }
            }
        }
    ],
    "meta": {
        "count": 1,
        "lat": 33.51,
        "lng": 36.28,
        "radius_km": 10
    }
}
```
