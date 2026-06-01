# 20 - قائمة التحقق للإنتاج (Production Checklist)

## التهيئة

- [ ] تكوين FCM credentials في `storage/app/fcm-credentials.json`
- [ ] تعيين `FCM_PROJECT_ID` في `.env`
- [ ] تكوين `TWILIO_SID`, `TWILIO_TOKEN`, `TWILIO_FROM` في `.env`
- [ ] تكوين `MAIL_*` في `.env`
- [ ] تشغيل `php artisan queue:work --queue=notifications`
- [ ] إعداد Supervisor لضمان استمرارية queue worker

## المراقبة

- [ ] مراقبة معدل فشل الإشعارات (يجب < 1%)
- [ ] مراقبة وقت المعالجة (يجب < 5 ثوانٍ)
- [ ] مراقبة حجم queue (يجب < 1000)
- [ ] إعداد تنبيهات عند فشل القناة
- [ ] تسجيل جميع أخطاء الإرسال

## الأداء

- [ ] زيادة worker count: `php artisan queue:work --queue=notifications --sleep=3 --tries=3`
- [ ] استخدام Redis cluster للـ queue
- [ ] تفعيل batch processing للإشعارات الجماعية
- [ ] تخزين مؤقت للـ templates

## الأمان

- [ ] تدوير FCM credentials كل 90 يوم
- [ ] تقييد API الإرسال للمشرفين فقط
- [ ] Rate limiting: 5 SMS/hour/user, 10 Emails/hour/user
- [ ] تنظيف device tokens غير النشطة شهرياً

```php
<?php

// Artisan command لتنظيف التوكنز
namespace App\Console\Commands;

use App\Models\DeviceToken;
use Illuminate\Console\Command;

class CleanDeviceTokens extends Command
{
    protected $signature = 'notifications:clean-tokens';
    protected $description = 'حذف توكنز FCM غير النشطة';

    public function handle()
    {
        $deleted = DeviceToken::where('updated_at', '<', now()->subMonths(3))
            ->orWhere('is_active', false)
            ->delete();

        $this->info("تم حذف {$deleted} توكن غير نشط");
    }
}
```

## قائمة التحقق النهائية

- [ ] جميع الخدمات (FCM, Twilio, Mail) مختبرة
- [ ] Queue workers تعمل
- [ ] الـ API موثقة
- [ ] الاختبارات تمر بنجاح
- [ ] مراقبة الأداء مفعلة
- [ ] النسخ الاحتياطي للـ logs
- [ ] توثيق عملية التشغيل
