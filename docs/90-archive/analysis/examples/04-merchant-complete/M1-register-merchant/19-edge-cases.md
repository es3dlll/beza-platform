# 19 - حالات الحافة (Edge Cases)

## السيناريوهات الكاملة

**1. تسجيل مكرر بنفس السجل التجاري**
```php
$exists = Merchant::where('cr_number', $request->cr_number)->exists();
if ($exists) {
    throw new DuplicateCrException('السجل التجاري مستخدم مسبقاً');
}
```
الحل: UNIQUE constraint على `cr_number` + فحص قبل الإدخال. إرجاع 422 مع رسالة واضحة.

**2. سجل تجاري منتهي الصلاحية**
```php
if ($request->cr_expiry_date < now()) {
    throw new ExpiredCrException('السجل التجاري منتهي الصلاحية');
}
```
فحص تاريخ انتهاء السجل التجاري قبل قبول الطلب. رفض مع 422.

**3. إعادة تقديم بعد رفض المستندات**
```php
$merchant = Merchant::where('user_id', $userId)->where('status', 'rejected')->first();
if ($merchant && $request->hasFile('documents')) {
    $merchant->update(['status' => 'pending', 'documents' => $paths]);
    // إرسال إشعار للتاجر باستلام المستندات الجديدة
}
```
المستندات المرفوضة: التاجر يعيد رفعها، يعود الطلب إلى pending للمراجعة من جديد.

**4. تسجيل بنفس رقم الهاتف كمستخدم موجود**
```php
$user = User::where('phone', $request->phone)->first();
if ($user && $user->merchant) {
    throw new UserAlreadyMerchantException('هذا المستخدم لديه حساب تاجر بالفعل');
}
```
فحص: phone → User → Merchant موجود مسبقاً → رفض مع نص "لديك حساب تاجر بالفعل".

**5. وكيل يحاول التسجيل كتاجر (Agent Impersonation)**
يحاول الوكيل تسجيل متجر باسمه. يمنع بـ role check: `if ($user->role === 'agent') throw new AgentCantRegisterException()`. يُطلب من الوكيل إنشاء حساب جديد بريد إلكتروني مختلف.

**6. سرقة الهوية (Identity Theft)**
```php
// التحقق من مطابقة الاسم في السجل التجاري مع الاسم في الهوية
$crData = $this->verifyCrWithGovernment($request->cr_number);
if ($crData->owner_name !== $request->owner_name) {
    throw new IdentityMismatchException('اسم المالك لا يطابق السجل التجاري');
}
```
التحقق عبر API حكومي حقيقي. عدم التطابق → رفض فوري وإبلاغ الجهات المختصة.

**7. فشل التحقق من الحساب البنكي**
```php
$verified = $this->bankService->verifyIban($request->iban);
if (!$verified) {
    MerchantVerificationLog::create(['merchant_id' => $merchant->id, 'type' => 'iban', 'status' => 'failed']);
    throw new BankVerificationFailedException('IBAN غير صالح أو لا يطابق اسم التاجر');
}
```
يحاول النظام 3 مرات ثم يوقف المحاولة ويتطلب تدخل يدوي من الدعم الفني.

## جدول حالات الحافة الكامل
| # | الحالة | آلية الكشف | النتيجة |
|---|--------|------------|---------|
| 1 | سجل تجاري مكرر | UNIQUE + فحص Laravel | 422 - السجل موجود بالفعل |
| 2 | سجل تجاري منتهي | مقارنة مع today() | 422 - يرجى تجديد السجل |
| 3 | مستندات مرفوضة | status === rejected | pending لإعادة المراجعة |
| 4 | هاتف مستخدم مسبقاً | join User + Merchant | 422 - لديك حساب بالفعل |
| 5 | وكيل يتظاهر كتاجر | role !== 'merchant' | 403 - غير مسموح |
| 6 | سرقة هوية | API حكومي | رفض + بلاغ |
| 7 | IBAN خاطئ | Bank Service API | رفض + log + إشعار دعم |

## كود معالجة كامل للحالات
```php
public function handleEdgeCases(array $data): void
{
    throw_if(
        Merchant::where('cr_number', $data['cr_number'])->exists(),
        DuplicateCrException::class
    );
    throw_if(
        Carbon::parse($data['cr_expiry_date'])->isPast(),
        ExpiredCrException::class
    );
    throw_if(
        User::where('phone', $data['phone'])->whereHas('merchant')->exists(),
        UserAlreadyMerchantException::class
    );
    throw_if(
        auth()->user()->role === 'agent',
        AgentCantRegisterException::class
    );
}
```

## ملخص
هذه الحالات تغطي 80% من سيناريوهات الفشل في تسجيل التجار. كل حالة تعيد رسالة خطأ واضحة بالعربية مع كود HTTP مناسب لتمكين التطبيق من عرضها بشكل احترافي للمستخدم.
