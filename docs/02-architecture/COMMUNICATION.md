# Inter-Module Communication - التواصل بين الوحدات

## القاعدة الذهبية

لا استدعاءات مباشرة بين الوحدات مطلقاً. كل التواصل يتم عبر الأحداث (Event Bus).

## التدفق الصحيح

```
وحدة "أ" تطلق حدثاً
        │
        ▼
   Event Bus
        │
        ▼
وحدة "ب" تستمع للحدث ← Listener (ShouldQueue) ← handle(Event)
```

## مثال عملي

```php
// في وحدة Identity - بعد تسجيل مستخدم جديد
event(new UserRegistered($user));

// في وحدة Compliance - مستمع للحدث
class UserRegisteredListener implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        // إنشاء سجل KYC للمستخدم الجديد
        // فحص العقوبات
        // تحديد المخاطر الأولية
    }
}
```

## مكونات Event Bus

| المكون | المسؤولية |
|--------|-----------|
| **EventPublisher** | نشر الأحداث مع ضمان التسليم |
| **ConsumerRegistry** | تسجيل المستهلكين لكل حدث |
| **SchemaVersionManager** | إدارة إصدارات الأحداث للتوافق |
| **PoisonPillMonitor** | مراقبة الأحداث الفاشلة المتكررة |
| **RetryPolicy** | سياسة إعادة المحاولة (exponential backoff) |
| **DeadLetterQueue** | الأحداث الفاشلة بعد استنفاذ المحاولات |

## الممنوعات

- استدعاء Service من وحدة أخرى عبر `app()` أو الحقن
- إنشاء Model أو استعلام مباشر على جداول وحدة أخرى
- استدعاء Controller أو Route خاص بوحدة أخرى
- مشاركة قاعدة البيانات مباشرة بين الوحدات

## الاستثناء الوحيد المسموح

الوحدات المشتركة في `Core/` يمكن استدعاؤها عبر واجهات رسمية موثقة فقط.
