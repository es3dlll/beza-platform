# 20 - تدقيق الأمان (Security Audit) - بوابة الدفع (Payment Gateway)

## توكن الرابط (Link Token)
```php
// 64 حرف عشوائي آمن = 2^256 احتمال
bin2hex(random_bytes(32));
```

## منع تخمين التوكن
64 حرف عشوائي — غير قابل للتخمين. يستخدم random_bytes من PHP (آمن تشفيرياً).

## التحقق من الصلاحية
```php
if ($link->expires_at->isPast()) { throw new PaymentLinkExpiredException(); }
```

## قائمة التحقق الأمنية (Security Checklist)
| # | البند | الحالة | شرح |
|---|-------|--------|------|
| 1 | توكن عشوائي آمن | ✅ | 64 حرف hex من random_bytes |
| 2 | صلاحية محددة | ✅ | تنتهي بعد expiry_hours |
| 3 | تجميد الرصيد | ✅ | يضمن تغطية المبلغ |
| 4 | منع الدفع المكرر | ✅ | status=used + row lock |
| 5 | HTTPS | ✅ | مطلوب للإنتاج |
| 6 | Rate limiting | ✅ | throttle على إنشاء الروابط |
| 7 | Webhook HMAC | ✅ | توقيع الطلبات |
| 8 | Validation كامل | ✅ | FormRequest + custom rules |

## توصيات إضافية
- إضافة two-factor authentication للعمليات الحرجة
- تسجيل جميع محاولات الدفع الفاشلة للتدقيق
- استخدام Laravel Horizon لإدارة الـ queues والـ webhooks
