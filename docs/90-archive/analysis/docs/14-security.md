# 14. الأمان والحماية متعدد المستويات

## 14.1 قائمة شاملة للإجراءات الأمنية

| المستوى | الإجراء |
|---------|---------|
| الشبكة | WAF (Cloudflare), حماية DDoS, TLS 1.3 فقط, إخفاء إصدار الخادم |
| الخادم | تحديثات أمنية أسبوعية, تشغيل PHP كـ user منخفض الصلاحية, SELinux, fail2ban |
| التطبيق | إدخال صارم (validation), منع SQL injection (Eloquent prepared statements), إسكيب الإخراج (Blade/XSS), CSRF tokens في الواجهات |
| المصادقة | 2FA إجبارية للمبالغ > 1000 USD, قفل بعد 5 محاولات PIN خاطئة, جلسات تنتهي بعد 30 دقيقة من الخمول, إعادة المصادقة للعمليات الحساسة |
| البيانات | تشفير AES-256 للحقول الحساسة (مثل pin_code, two_factor_secret), تشفير النسخ الاحتياطي, تدوير المفاتيح كل 90 يوماً |
| التدقيق | تسجيل كل عملية تغيير في الرصيد أو الحالة, تسجيل محاولات الدخول الفاشلة, الاحتفاظ بالسجلات 7 سنوات |
| الامتثال | PCI DSS Level 1 (للمدفوعات), GDPR (للخصوصية), قوانين مكافحة غسل الأموال |
| اختبار الاختراق | إجراء اختبار اختراق ربع سنوي من جهة خارجية |

## 14.2 تنفيذ 2FA باستخدام TOTP (Google Authenticator)

```php
// في AuthController
public function enable2fa(Request $request)
{
    $user = $request->user();
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();
    $user->two_factor_secret = encrypt($secret);
    $user->save();

    $qrCodeUrl = $google2fa->getQRCodeUrl(
        config('app.name'),
        $user->email,
        $secret
    );

    return $this->success(['qr_code' => $qrCodeUrl, 'secret' => $secret]);
}

public function verify2fa(Request $request)
{
    $request->validate(['code' => 'required|string|size:6']);
    $user = $request->user();
    $secret = decrypt($user->two_factor_secret);
    $google2fa = new Google2FA();
    $valid = $google2fa->verifyKey($secret, $request->code);

    if ($valid) {
        $user->two_factor_confirmed = true;
        $user->save();
        return $this->success(null, 'تم تفعيل المصادقة الثنائية');
    }
    return $this->error('رمز غير صحيح', 400);
}
```

## 14.3 منع الاحتيال (Fraud Detection)

- كشف الأنشطة غير المعتادة: تغيير مفاجئ في نمط المعاملات، محاولات تحويل كبيرة من جهاز جديد
- قائمة IPs المحظورة: مزامنة مع قواعد بيانات موثوقة
- مراجعة يدوية للمعاملات الكبيرة أو المشبوهة
