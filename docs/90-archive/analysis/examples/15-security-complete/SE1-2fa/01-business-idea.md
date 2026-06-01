# 01 - أهمية 2FA للأمان (Business Idea)

## لماذا المصادقة الثنائية؟

- كلمة المرور و PIN يمكن سرقتها أو تخمينها
- 2FA يضيف طبقة أمان إضافية: شيء تملكه (الهاتف)
- بدون 2FA، 80% من الاختراقات تكون عبر كلمة المرور فقط

## الحالات الإجبارية

```php
// المعاملات الكبيرة
if ($amount > 1000 && $currency === 'USD') {
    throw new TwoFactorRequiredException();
}

// حسابات المشرفين
if ($user->is_admin) {
    throw new TwoFactorRequiredException();
}

// تغيير PIN البطاقة
if ($action === 'change_card_pin') {
    throw new TwoFactorRequiredException();
}
```

## تدفق تفعيل 2FA

```
1. المستخدم يطلب تفعيل 2FA
2. النظام يولد secret key (TOTP)
3. عرض QR code في التطبيق
4. المستخدم يمسح QR بـ Google Authenticator
5. المستخدم يدخل الرمز للتحقق
6. تفعيل 2FA → يتم توليد 8 رموز استرداد
7. عرض رموز الاسترداد (مرة واحدة فقط)
```
