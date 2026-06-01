# N1 - نظام الإشعارات

## الوصف
إرسال الإشعارات عبر قنوات متعددة: Push (FCM)، SMS (Twilio)، Email.

## أنواع الإشعارات

### 1. إشعارات OTP
- القناة: SMS
- متى: عند طلب OTP للتحقق من رقم الهاتف
- النص: "رمز التحقق Beza: {otp}"

### 2. إشعارات المعاملات
- القناة: Push (FCM)
- متى: استلام تحويل
- النص: "لقد استلمت {amount} {currency} من {name}"

- القناة: Push (FCM)
- متى: إرسال تحويل
- النص: "تم تحويل {amount} {currency} إلى {phone}"

### 3. إشعارات الـ KYC
- القناة: Push + Email
- متى: الموافقة على KYC
- النص: "تم توثيق حسابك بنجاح. يمكنك الآن استخدام جميع الخدمات."

- القناة: Push + Email
- متى: رفض KYC
- النص: "لم يتم توثيق حسابك. السبب: {reason}"

### 4. إشعارات الصفقات الاستثمارية
- القناة: Push
- متى: اكتمال الصفقة + توزيع الأرباح
- النص: "تم إتمام الصفقة {title}. تم إيداع {amount} {currency} في محفظتك."

### 5. إشعارات المشرف
- القناة: Push + Email
- متى: طلب تاجر/وكيل جديد
- النص: "طلب تاجر جديد: {business_name}"

### 6. إشعارات أمنية
- القناة: SMS + Push
- متى: تغيير كلمة السر
- النص: "تم تغيير كلمة السر لحسابك. إذا لم تقم بذلك، تواصل مع الدعم."

- القناة: SMS
- متى: محاولات PIN خاطئة
- النص: "تم إدخال PIN خاطئ 5 مرات. تم قفل الحساب 15 دقيقة."

## الخدمة
```php
class NotificationService {
    public static function sendPush($token, $title, $body) { /* FCM */ }
    public static function sendSms($phone, $message) { /* Twilio */ }
    public static function sendEmail($email, $subject, $body) { /* Mail */ }
}
```

## API Endpoint
`GET /api/v1/notifications` (قائمة إشعارات المستخدم)
`POST /api/v1/notifications/{id}/read` (تحديد كمقروء)

## اختبارات
- إرسال إشعار push ← 200 (FCM)
- إرسال SMS ← 200 (Twilio mock)
- عرض قائمة الإشعارات ← 200
- تحديد إشعار كمقروء ← 200
