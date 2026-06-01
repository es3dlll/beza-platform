# 09 - سيرفس لير العملية (Service Layer)

## AgentRegistrationService

```php
<?php

namespace App\Services;

use App\Models\AgentRequest;
use App\Models\User;
use App\Events\AgentRegistered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AgentRegistrationService
{
    public function register(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            // رفع صورة الهوية
            $idPhotoPath = $data['id_photo']->store(
                'agent-documents/' . $user->id,
                'local'
            );

            // إنشاء طلب التسجيل
            $agentRequest = AgentRequest::create([
                'user_id' => $user->id,
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'id_number' => $data['id_number'],
                'id_photo_path' => $idPhotoPath,
                'location_lat' => $data['location_lat'] ?? null,
                'location_lng' => $data['location_lng'] ?? null,
                'status' => 'pending',
            ]);

            // إطلاق حدث
            event(new AgentRegistered($agentRequest, $user));

            return [
                'request_id' => $agentRequest->id,
                'status' => 'pending',
            ];
        }, attempts: 3);
    }
}
```

## تدفق الخدمة

1. استقبال البيانات المدققة (validated)
2. رفع صورة الهوية إلى التخزين المحلي
3. إنشاء سجل طلب وكيل بحالة pending
4. إطلاق حدث AgentRegistered لإشعار الإدارة
5. إرجاع معرف الطلب والحالة
