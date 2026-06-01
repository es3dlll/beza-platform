# 19 - حالات الحافة (Edge Cases)

## معالجة الأخطاء الشائعة

| الحالة | المعالجة |
|--------|----------|
| FCM Token منتهي | تعطيل التوكن، إرسال إشعار بديل عبر SMS |
| رقم هاتف غير صالح في Twilio | تسجيل الخطأ، تخطي القناة |
| Email غير مؤكد | إرسال إشعار داخل التطبيق فقط |
| Queue معطلة (Redis) | إرسال متزامن (sendNow) |
| وصول حد Twilio | تخزين في queue والمحاولة لاحقاً |
| تزامن 1000 إشعار | استخدام queue مع 20 worker |
| إشعار 0 بايت | تجاهل، عدم الإرسال |
| أحرف خاصة في المحتوى | sanitize قبل الإرسال |

## Sanitization

```php
<?php

trait SanitizesNotificationContent
{
    protected function sanitize(string $text): string
    {
        // إزالة HTML
        $text = strip_tags($text);
        // ترميز UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        // إزالة المسافات الزائدة
        $text = preg_replace('/\s+/', ' ', $text);
        // قص الطول
        return mb_substr(trim($text), 0, 1000);
    }
}
```

## Rate Limiting

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class NotificationRateLimiter
{
    public function canSend(int $userId, string $channel): bool
    {
        $key = "rate_limit:{$channel}:{$userId}";

        $attempts = Cache::get($key, 0);
        $limit = match ($channel) {
            'sms' => 5,    // 5 SMS في الساعة
            'email' => 10,  // 10 Emails في الساعة
            default => 50,
        };

        if ($attempts >= $limit) {
            return false;
        }

        Cache::increment($key);
        Cache::expire($key, 3600); // ساعة

        return true;
    }
}
```

## قائمة تحقق الأمان

- [ ] التحقق من ملكية الإشعار قبل تحديث read_at
- [ ] عدم كشف محتوى إشعارات الآخرين
- [ ] Rate limiting على إرسال الإشعارات
- [ ] التحقق من صحة FCM tokens قبل الإرسال
- [ ] تشفير البيانات الحساسة في الإشعار
- [ ] عدم إرسال OTP عبر push notification
- [ ] تسجيل جميع محاولات الإرسال
