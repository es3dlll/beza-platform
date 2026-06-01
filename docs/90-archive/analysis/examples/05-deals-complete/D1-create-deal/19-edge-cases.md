# 19 - حالات الحافة + سيناريوهات خطأ (Edge Cases) - إنشاء الصفقة

## نظرة عامة

هذا المستند يغطي جميع الحالات الطرفية والسيناريوهات غير المتوقعة التي قد تحدث أثناء إنشاء الصفقات في منصة بيزة. تم تحليل كل حالة وتحديد مستوى المعالجة المناسب وكود PHP للتعامل معها.

## جدول حالات الحافة

| # | الحالة | النتيجة | مستوى المعالجة | كود الخطأ |
|---|--------|---------|---------------|-----------|
| 1 | target_amount < 100 USD | رفض | Validation | DEAL_AMOUNT_TOO_LOW |
| 2 | مدة الصفقة 0 أو سالبة | رفض | Validation | DEAL_DURATION_INVALID |
| 3 | نسبة ربح > 100% | رفض | Validation | PROFIT_PERCENT_EXCEEDS_MAX |
| 4 | عنوان مكرر | رفض | unique validation | DEAL_TITLE_DUPLICATE |
| 5 | إنشاء صفقة من غير Admin | 403 | Middleware | UNAUTHORIZED_ACTION |
| 6 | target_amount يتجاوز DECIMAL(15,2) | رفض | DB + Validation | AMOUNT_OVERFLOW |
| 7 | description طويل جداً (>5000) | رفض | Validation | DESCRIPTION_TOO_LONG |
| 8 | إنشاء صفقتين في نفس الوقت | كلاهما ينجح (لا تعارض) | Normal | - |
| 9 | Admin غير نشط (suspended) | 401 | Auth | ADMIN_SUSPENDED |
| 10 | missing required fields | 422 | Validation | VALIDATION_ERROR |
| 11 | إنشاء صفقة مكررة (نفس الفاتورة) | رفض | Business | DUPLICATE_INVOICE |
| 12 | target_amount تم تجاوزه بعد التعديل | رفض | Business | TARGET_EXCEEDED |
| 13 | Admin ينشئ صفقة ثم يحذفها قبل أي استثمار | مسموح (حذف ناعم) | Business | - |
| 14 | نسبة ربح 0% أو غير واقعية (99.99%) | تحذير + قبول | Validation | PROFIT_SUSPICIOUS |
| 15 | مدة الصفقة أقل من الحد الأدنى (يوم) | رفض | Validation | DURATION_BELOW_MINIMUM |
| 16 | عدم تطابق العملة بين الصفقة والمحفظة | رفض | Service | CURRENCY_MISMATCH |
| 17 | انتهاء الصفقة دون الوصول للهدف | حالة خاصة (تنتهي بدون تمويل) | Business | DEAL_EXPIRED |

## تحليل الحالات بالتفصيل

### 1. target_amount < 100 USD
```php
// app/Services/DealValidationService.php
public function validateTargetAmount($amount, $currency): void
{
    $minAmount = $currency === 'USD' ? 100 : $this->getMinInSYP($currency);

    if ($amount < $minAmount) {
        throw new DealException(
            'مبلغ الهدف يجب أن يكون على الأقل ' . $minAmount . ' ' . $currency,
            'DEAL_AMOUNT_TOO_LOW'
        );
    }
}
```

### 2. مدة الصفقة 0 أو سالبة
```php
$validDurations = [7, 14, 30, 60, 90]; // أيام
if (!in_array((int)$request->duration_days, $validDurations)) {
    throw new DealException('مدة الصفقة غير صالحة. المدد المسموحة: 7, 14, 30, 60, 90 يوماً');
}
```

### 3. نسبة ربح > 100%
```php
// لا يمكن أن يتجاوز الربح 100% من قيمة الصفقة
if ($request->profit_percent > 100) {
    throw new DealException('نسبة الربح لا يمكن أن تتجاوز 100%', 'PROFIT_PERCENT_EXCEEDS_MAX');
}
```

### 4. عنوان مكرر
```php
$exists = Deal::where('title', $request->title)->exists();
if ($exists) {
    throw new DealException('يوجد صفقة بنفس العنوان بالفعل', 'DEAL_TITLE_DUPLICATE');
}
```

### 5. إنشاء صفقة من غير Admin
```php
// DealController.php
public function __construct()
{
    $this->middleware('auth:api');
    $this->middleware('role:admin'); // 403 للمستخدمين العاديين
}
```

### 6. target_amount يتجاوز DECIMAL(15,2)
الحد الأقصى: 9999999999999.99 (أي حوالي 10 تريليون دولار)
```php
'amount' => 'required|numeric|min:100|max:9999999999999.99'
```

### 7. description طويل جداً
```php
'description' => 'required|string|max:5000'
```

### 8. إنشاء صفقتين في نفس الوقت
```php
// لا يوجد تعارض لأن كل صفقة لها ID مستقل (UUID)
$deal = Deal::create([...]); // الأولى
$deal2 = Deal::create([...]); // الثانية - تنجح أيضاً
```

### 9. Admin غير نشط
```php
if ($admin->status !== 'active') {
    abort(401, 'حساب المسؤول غير نشط. الرجاء التواصل مع الدعم الفني.');
}
```

### 10. missing required fields
```php
$rules = [
    'title' => 'required|string|max:255',
    'description' => 'required|string|max:5000',
    'target_amount' => 'required|numeric|min:100',
    'currency' => 'required|in:USD,SYP',
    'duration_days' => 'required|integer|in:7,14,30,60,90',
    'profit_percent' => 'required|numeric|min:0|max:100',
];
$validator = Validator::make($request->all(), $rules);
if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}
```

### 11. إنشاء صفقة مكررة (نفس الفاتورة)
```php
// التأكد من عدم استخدام نفس invoice_id أو shipment_id مرتين
$duplicate = Deal::where('invoice_id', $request->invoice_id)
    ->where('status', '!=', 'deleted')
    ->exists();

if ($duplicate) {
    throw new DealException('هذه الفاتورة مستخدمة بالفعل في صفقة أخرى', 'DUPLICATE_INVOICE');
}
```

### 12. target_amount تجاوز بعد التعديل
```php
// إذا حاول Admin تعديل target_amount إلى قيمة أقل من مجموع الاستثمارات الحالية
$totalInvested = $deal->investments()->sum('amount');
if ($request->target_amount < $totalInvested) {
    throw new DealException(
        'لا يمكن تخفيض المبلغ المستهدف إلى أقل من مجموع الاستثمارات الحالية (' . $totalInvested . ')',
        'TARGET_EXCEEDED'
    );
}
```

### 13. Admin ينشئ صفقة ثم يحذفها قبل أي استثمار
```php
// حذف ناعم (Soft Delete)
$deal = Deal::findOrFail($id);
if ($deal->investments()->count() > 0) {
    throw new DealException('لا يمكن حذف صفقة بها استثمارات');
}
$deal->delete(); // Soft delete - يبقى في قاعدة البيانات مع deleted_at
```

### 14. نسبة ربح 0% أو غير واقعية
```php
// تحذير ولكن يسمح (قد يرغب Admin في إنشاء صفقة غير ربحية)
if ($request->profit_percent == 0) {
    Log::warning('تم إنشاء صفقة بنسبة ربح 0%', ['deal_title' => $request->title]);
}
if ($request->profit_percent > 50) {
    // نسبة ربح عالية تحتاج مراجعة
    $request->request->add(['requires_review' => true]);
}
```

### 15. مدة الصفقة أقل من الحد الأدنى
```php
$minimumDuration = 7; // أيام
if ($request->duration_days < $minimumDuration) {
    throw new DealException('الحد الأدنى لمدة الصفقة هو ' . $minimumDuration . ' أيام');
}
```

### 16. عدم تطابق العملة
```php
// التحقق من أن Admin يملك محفظة بنفس عملة الصفقة
$adminWallet = $admin->wallets()->where('currency', $request->currency)->first();
if (!$adminWallet) {
    throw new DealException(
        'ليس لديك محفظة بعملة ' . $request->currency . '. الرجاء إنشاء محفظة أولاً.',
        'CURRENCY_MISMATCH'
    );
}
```

### 17. انتهاء الصفقة دون الوصول للهدف
```php
// CronJob يومي لفحص الصفقات المنتهية
Schedule::daily()->call(function () {
    $expiredDeals = Deal::where('status', 'active')
        ->where('end_date', '<', now())
        ->whereColumn('current_amount', '<', 'target_amount')
        ->get();

    foreach ($expiredDeals as $deal) {
        $deal->status = 'expired';
        $deal->save();
        // إشعار المستثمرين
        event(new DealExpiredEvent($deal));
    }
});
```

## مصفوفة القرار

| الشرط | النتيجة |
|-------|---------|
| target_amount < 100 | رفض تام |
| مدة < 7 أيام | رفض تام |
| ربح > 100% | رفض تام |
| عنوان مكرر | منع الإدخال |
| invoice_id مكرر | منع الإدخال |
| المحذوف بدون استثمارات | مسموح مع soft delete |
| صفقتان متزامنتان | كلاهما يمر (لا تضارب) |
| عملة غير متوفرة في المحفظة | رفض مع توجيه لإنشاء محفظة |
| وصف طويل جداً | رفض مع تحديد الحد |

## توصيات إضافية

1. إضافة **Audit Log** لكل عملية إنشاء أو تعديل صفقة
2. استخدام **UUID** بدلاً من ID الرقمي لمنع التخمين
3. إرسال **إشعار** لجميع المستثمرين المحتملين عند إنشاء صفقة جديدة
4. تطبيق **Rate Limiting** على واجهة إنشاء الصفقة (10 محاولات / دقيقة)
5. إضافة **Captain** في جلسة المستخدم لمنع إعادة إرسال الطلب
