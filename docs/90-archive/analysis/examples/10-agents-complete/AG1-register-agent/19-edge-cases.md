# 19 - حالات الحافة (Edge Cases) - تسجيل الوكيل

## 1. هوية وطنية مكررة (Duplicate National ID)

```php
<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentRequest;
use App\Exceptions\AgentAlreadyExistsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateNationalIdHandler
{
    /**
     * معالجة محاولة تسجيل بهوية وطنية مكررة
     */
    public function handleDuplicateNationalId(string $nationalId, int $userId): void
    {
        // البحث في جميع الحالات الممكنة
        $existingAgent = Agent::where('national_id', $nationalId)->first();
        $pendingRequest = AgentRequest::where('national_id', $nationalId)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingAgent) {
            Log::warning('محاولة تسجيل بهوية وطنية موجودة لوكيل نشط', [
                'national_id' => $nationalId,
                'existing_agent_id' => $existingAgent->id,
                'attempted_by_user' => $userId,
            ]);

            throw new AgentAlreadyExistsException(
                userId: $userId,
                message: 'رقم الهوية الوطنية مسجل لوكيل نشط بالفعل. يرجى التواصل مع الدعم.',
            );
        }

        if ($pendingRequest) {
            Log::info('محاولة تسجيل بهوية وطنية موجودة في طلب معلق', [
                'national_id' => $nationalId,
                'existing_request_id' => $pendingRequest->id,
                'attempted_by_user' => $userId,
            ]);

            throw new AgentAlreadyExistsException(
                userId: $userId,
                message: 'يوجد طلب تسجيل معلق بهذا الرقم. يرجى انتظار الموافقة.',
            );
        }
    }

    /**
     * التحقق خلال المعاملة للتأكد من عدم حدوث شرط سباق (Race Condition)
     */
    public function lockAndVerify(string $nationalId): bool
    {
        return DB::transaction(function () use ($nationalId) {
            $locked = DB::table('agents')
                ->where('national_id', $nationalId)
                ->lockForUpdate()
                ->exists();

            $lockedRequest = DB::table('agent_requests')
                ->where('national_id', $nationalId)
                ->whereIn('status', ['pending', 'approved'])
                ->lockForUpdate()
                ->exists();

            return !$locked && !$lockedRequest;
        });
    }
}
```

## 2. تزوير الموقع (Location Spoofing)

```php
<?php

namespace App\Services;

use App\Exceptions\LocationOutOfBoundsException;
use App\Models\Agent;
use Illuminate\Support\Facades\Log;

class LocationSpoofingDetector
{
    private const MAX_SPEED_KMH = 120;
    private const EARTH_RADIUS_KM = 6371;

    /**
     * كشف محاولات تزوير الموقع
     */
    public function detectSpoofing(array $location, int $userId): void
    {
        // التحقق من الحدود الجغرافية
        $this->validateGeographicBounds($location);

        // التحقق من السرعة الغير معقولة (مقارنة بآخر موقع معروف)
        $this->checkSpeedAgainstLastKnownLocation($location, $userId);

        // التحقق من دقة الإحداثيات (GPS spoofing غالباً ما يعطي أرقاماً مستديرة)
        $this->checkCoordinatePrecision($location);
    }

    private function validateGeographicBounds(array $location): void
    {
        $lat = $location['lat'];
        $lng = $location['lng'];

        if ($lat < 16.5 || $lat > 32.5 || $lng < 34.5 || $lng > 56.0) {
            Log::warning('محاولة تزوير موقع - خارج الحدود', [
                'lat' => $lat,
                'lng' => $lng,
            ]);
            throw new LocationOutOfBoundsException($lat, $lng);
        }
    }

    private function checkSpeedAgainstLastKnownLocation(array $location, int $userId): void
    {
        $lastLocation = Agent::where('user_id', $userId)
            ->whereNotNull('location')
            ->latest()
            ->first();

        if ($lastLocation?->location) {
            $lastLat = $lastLocation->location->getLat();
            $lastLng = $lastLocation->location->getLng();
            $lastTime = $lastLocation->updated_at;

            $distance = $this->haversineDistance(
                $lastLat, $lastLng,
                $location['lat'], $location['lng']
            );

            $hoursSince = $lastTime->diffInHours(now());
            if ($hoursSince > 0) {
                $speed = $distance / $hoursSince;
                if ($speed > self::MAX_SPEED_KMH) {
                    Log::warning('سرعة غير معقولة - احتمال تزوير موقع', [
                        'user_id' => $userId,
                        'speed_kmh' => $speed,
                        'distance_km' => $distance,
                        'hours' => $hoursSince,
                    ]);
                }
            }
        }
    }

    private function checkCoordinatePrecision(array $location): void
    {
        $lat = (string) $location['lat'];
        $lng = (string) $location['lng'];

        // GPS المزيف غالباً ما ينتهي بـ .000000
        if (str_ends_with($lat, '000000') || str_ends_with($lng, '000000')) {
            Log::warning('إحداثيات مشبوهة - دقة غير طبيعية', [
                'lat' => $lat,
                'lng' => $lng,
            ]);
        }
    }

    private function haversineDistance(
        float $lat1, float $lng1,
        float $lat2, float $lng2,
    ): float {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return self::EARTH_RADIUS_KM * 2 * asin(sqrt($a));
    }
}
```

## 3. طلبات معلقة متعددة (Multiple Pending Applications)

```php
<?php

namespace App\Services;

use App\Models\AgentRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PendingApplicationHandler
{
    /**
     * منع المستخدم من تقديم أكثر من طلب معلق
     */
    public function enforceSinglePendingRequest(User $user): void
    {
        $pendingCount = AgentRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingCount >= 1) {
            Log::info('محاولة تقديم طلب معلق ثاني', [
                'user_id' => $user->id,
                'pending_count' => $pendingCount,
            ]);

            abort(409, 'لديك طلب تسجيل معلق بالفعل. يرجى انتظار الموافقة أو التواصل مع الدعم.');
        }
    }

    /**
     * تنظيف الطلبات المعلقة القديمة (أكثر من 7 أيام)
     */
    public function autoExpireOldPendingRequests(): int
    {
        $expired = AgentRequest::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(7))
            ->update([
                'status' => 'expired',
                'admin_notes' => DB::raw("JSON_SET(COALESCE(admin_notes, '{}'), '$.auto_expired', 'انتهت صلاحية الطلب تلقائياً بعد 7 أيام')"),
                'reviewed_at' => now(),
            ]);

        if ($expired > 0) {
            Log::info("تم انتهاء صلاحية {$expired} طلب تسجيل تلقائياً");
        }

        return $expired;
    }
}
```

## 4. تغيير نسبة العمولة (Commission Rate Changes)

```php
<?php

namespace App\Services;

use App\Models\Agent;
use App\Exceptions\CommissionRateInvalidException;
use Illuminate\Support\Facades\Log;
use App\Events\CommissionRateChanged;

class CommissionRateChangeHandler
{
    private const MIN_RATE = 0.001;
    private const MAX_RATE = 0.100;
    private const MAX_CHANGE_PER_DAY = 0.020;

    /**
     * معالجة تغيير نسبة العمولة مع منع التجاوزات
     */
    public function changeCommissionRate(Agent $agent, float $newRate): void
    {
        // التحقق من النطاق
        if ($newRate < self::MIN_RATE || $newRate > self::MAX_RATE) {
            throw new CommissionRateInvalidException(
                rate: $newRate,
                message: 'نسبة العمولة يجب أن تكون بين 0.1% و 10%.',
            );
        }

        // التحقق من التغيير اليومي المسموح به
        $this->validateDailyChangeLimit($agent, $newRate);

        // تسجيل التغيير في السجل
        $oldRate = $agent->commission_rate;
        $agent->update(['commission_rate' => $newRate]);

        Log::info('تم تغيير نسبة عمولة الوكيل', [
            'agent_id' => $agent->id,
            'old_rate' => $oldRate,
            'new_rate' => $newRate,
        ]);

        event(new CommissionRateChanged($agent, $oldRate, $newRate));
    }

    private function validateDailyChangeLimit(Agent $agent, float $newRate): void
    {
        $change = abs($newRate - $agent->commission_rate);
        if ($change > self::MAX_CHANGE_PER_DAY) {
            throw new CommissionRateInvalidException(
                rate: $newRate,
                message: 'لا يمكن تغيير نسبة العمولة بأكثر من 2% في اليوم الواحد.',
            );
        }
    }
}
```

## 5. سيناريوهات إضافية

| الحالة | المشكلة | الحل |
|--------|---------|------|
| هوية مكررة | رقم هوية مسجل لوكيل نشط | رفض الطلب مع رسالة توضيحية |
| تزوير موقع | إحداثيات خارج المملكة أو سرعة غير معقولة | رفض الطلب وتسجيل محاولة الاختراق |
| طلبات معلقة متعددة | مستخدم يقدم أكثر من طلب | منع الطلب الثاني حتى يُرفض الأول |
| تغيير العمولة | تغيير نسبة العمولة بشكل متكرر | تطبيق حد أقصى للتغيير اليومي |
| انتهاء الطلبات | طلبات معلقة لأكثر من 7 أيام | انتهاء صلاحية تلقائي مع إشعار |
| سباق تسجيل | طلبان متزامنان بنفس الهوية | استخدام row-level locking (lockForUpdate) |
