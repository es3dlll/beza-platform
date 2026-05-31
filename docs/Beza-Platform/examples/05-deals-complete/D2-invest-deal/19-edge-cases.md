# 19 - حالات الحافة + سيناريوهات خطأ (Edge Cases) - الاستثمار في الصفقة

## نظرة عامة

تغطي هذه الوثيقة جميع الحالات الطرفية لعملية الاستثمار في الصفقات. عملية الاستثمار هي الأكثر حساسية لأنها تتعامل مع أموال حقيقية للمستخدمين. يجب التعامل مع كل حالة بدقة لضمان عدم فقدان الأموال أو ازدواجية الاستثمارات.

## جدول حالات الحافة

| # | الحالة | النتيجة | مستوى المعالجة | كود الخطأ |
|---|--------|---------|---------------|-----------|
| 1 | استثمار بمبلغ < 10 USD | رفض | Validation | INVESTMENT_MINIMUM |
| 2 | استثمار في صفقة مكتملة | رفض | Business | DEAL_ALREADY_COMPLETED |
| 3 | استثمار في صفقة pending | رفض | Business | DEAL_NOT_ACTIVE |
| 4 | استثمار يتجاوز target_amount | رفض مع عرض المتبقي | Business | AMOUNT_EXCEEDS_REMAINING |
| 5 | رصيد غير كافٍ | رفض | Service | INSUFFICIENT_BALANCE |
| 6 | مستثمر = منشئ الصفقة | رفض (اختياري) | Business | SELF_INVESTMENT_DISALLOWED |
| 7 | استثمارين متزامنين من نفس المستخدم | الثاني يفشل (رصيد غير كافٍ) | ACID | CONCURRENT_INVESTMENT |
| 8 | استثمار في صفقة بعملة ليس لديه محفظة لها | رفض | Service | WALLET_CURRENCY_MISSING |
| 9 | محفظة المستثمر موقوفة | رفض | Service | WALLET_FROZEN |
| 10 | نفس المستثمر يحاول الاستثمار مرتين | يُسمح (UNIQUE على المستوى المالي) | Business | - |
| 11 | محاولة استثمار أكثر من المبلغ المتبقي للصفقة | رفض + عرض المبلغ المتبقي | Business | AMOUNT_EXCEEDS_REMAINING |
| 12 | نقرة مزدوجة على زر الاستثمار (Double-click) | منع عبر loading state + captcha | UI + Backend | RACE_CONDITION_PREVENTED |
| 13 | رصيد المحفظة يساوي بالضبط مبلغ الاستثمار | مسموح (رصيد = مبلغ) | Service | - |
| 14 | محفظة مجمدة تحاول الاستثمار | رفض + رسالة توضيحية | Service | WALLET_FROZEN |
| 15 | مستثمر يستثمر ثم يطلب الإلغاء فوراً | يعامل كطلب إلغاء استثمار | Business | REVERSAL_REQUESTED |
| 16 | الصفقة تُغلق أثناء عملية الاستثمار (منتصف الطلب) | التراجع عن العملية | ACID | DEAL_CLOSED_MID_INVESTMENT |
| 17 | الحد الأدنى للاستثمار لم يتم الوصول إليه | رفض | Validation | MINIMUM_NOT_MET |

## تحليل الحالات بالتفصيل مع أكواد PHP

### 1. استثمار بمبلغ < 10 USD
```php
// InvestmentRequest.php - Form Request Validation
public function rules(): array
{
    return [
        'amount' => [
            'required',
            'numeric',
            'min:10',
            'max:' . $this->deal->remaining_amount,
        ],
    ];
}
```

### 2. استثمار في صفقة مكتملة
```php
if ($deal->status === DealStatus::COMPLETED) {
    throw new InvestException(
        'هذه الصفقة مكتملة ولا يمكن الاستثمار فيها حالياً',
        'DEAL_ALREADY_COMPLETED'
    );
}
```

### 3. استثمار في صفقة pending
```php
if ($deal->status === DealStatus::PENDING) {
    throw new InvestException(
        'الصفقة لم يتم تفعيلها بعد من قبل المسؤول',
        'DEAL_NOT_ACTIVE'
    );
}
```

### 4. استثمار يتجاوز target_amount
```php
$remaining = $deal->target_amount - $deal->current_amount;
if ($request->amount > $remaining) {
    throw new InvestException(
        "المبلغ المطلوب (\${$request->amount}) يتجاوز المبلغ المتبقي (\${$remaining}). "
        . "أقصى مبلغ يمكنك استثماره هو \${$remaining}",
        'AMOUNT_EXCEEDS_REMAINING',
        ['remaining' => $remaining]
    );
}
```

### 5. رصيد غير كافٍ
```php
// يتم التحقق داخل DB transaction لضمان عدم تغير الرصيد
DB::transaction(function () use ($user, $deal, $amount) {
    $wallet = $user->wallets()
        ->where('currency', $deal->currency)
        ->lockForUpdate() // قفل الصف لمنع القراءة المتزامنة
        ->firstOrFail();

    if ($wallet->balance < $amount) {
        throw new InvestException(
            'رصيدك غير كافٍ. الرصيد الحالي: ' . $wallet->balance . ' ' . $deal->currency,
            'INSUFFICIENT_BALANCE',
            ['balance' => $wallet->balance]
        );
    }

    // خصم الرصيد وإنشاء الاستثمار في نفس العملية
    $wallet->decrement('balance', $amount);
    $deal->increment('current_amount', $amount);
    $deal->investments()->create([
        'user_id' => $user->id,
        'amount' => $amount,
    ]);
});
```

### 6. مستثمر = منشئ الصفقة
```php
if ($deal->created_by === $user->id) {
    throw new InvestException(
        'لا يمكنك الاستثمار في صفقة قمت بإنشائها',
        'SELF_INVESTMENT_DISALLOWED'
    );
}
```

### 7. استثمارين متزامنين من نفس المستخدم (Race Condition)
```php
// الحل: استخدام pessimistic locking
$wallet = Wallet::where('user_id', $user->id)
    ->where('currency', $deal->currency)
    ->lockForUpdate()
    ->first();

// عندها أي طلب ثانٍ سينتظر حتى ينتهي الأول
// الطلب الثاني سيجد الرصيد ناقصاً وسيفشل
```

### 8. استثمار بعملة لا يملك المستخدم محفظة لها
```php
$wallet = $user->wallets()->where('currency', $deal->currency)->first();
if (!$wallet) {
    throw new InvestException(
        'ليس لديك محفظة بعملة ' . $deal->currency 
        . '. الرجاء إنشاء محفظة ' . $deal->currency . ' أولاً',
        'WALLET_CURRENCY_MISSING'
    );
}
```

### 9. محفظة المستثمر موقوفة
```php
if ($wallet->status === 'frozen') {
    throw new InvestException(
        'محفظتك موقوفة حالياً. الرجاء التواصل مع الدعم الفني لحل المشكلة.',
        'WALLET_FROZEN',
        ['support_email' => 'support@beza.com']
    );
}
```

### 10. نفس المستثمر يستثمر مرتين في نفس الصفقة
```php
// مسموح - يمكن للمستثمر زيادة استثماره
// يتم إنشاء سجلات استثمار متعددة (لا يوجد UNIQUE على user_id + deal_id)
```

### 11-17. حالات الحافة الإضافية

#### 11-12. Double-click Prevention
```php
// في الـ Backend نستخدم Captcha (unique request ID)
$captcha = $request->header('X-Idempotency-Key');
if (Investment::where('idempotency_key', $captcha)->exists()) {
    return response()->json(['message' => 'تمت معالجة هذا الطلب مسبقاً'], 409);
}

// في Flutter نعطل الزر أثناء المعالجة
// ElevatedButton(
//   onPressed: isInvesting ? null : () => invest(),
//   child: isInvesting ? CircularProgressIndicator() : Text('استثمر'),
// )
```

#### 13. رصيد = مبلغ الاستثمار بالضبط
```php
// مسموح - يصبح الرصيد 0 بعد الاستثمار
// تحذير: يجب التأكد من أن الرصيد لا يصبح سالباً
if ($wallet->balance < $amount) { // استخدام < وليس <=
    throw new InvestException('...');
}
// 👆 باستخدام < بدلاً من <= نسمح بـ balance == amount
```

#### 14. محفظة مجمدة (Frozen)
```php
// frozen قد يكون بسبب عدم إكمال KYC أو بلاغ احتيال
private function checkWalletNotFrozen(Wallet $wallet): void
{
    if ($wallet->is_frozen) {
        // تسجيل محاولة الوصول
        activity()
            ->causedBy($wallet->user)
            ->withProperties(['wallet_id' => $wallet->id])
            ->log('محاولة استثمار من محفظة مجمدة');

        throw new InvestException(
            'عذراً، محفظتك مجمدة. يرجى إكمال التحقق من الهوية (KYC) أولاً.',
            'WALLET_FROZEN'
        );
    }
}
```

#### 15. استثمار ثم طلب إلغاء فوري
```php
// يتم التعامل معها كـ "طلب إلغاء استثمار" منفصل
// في D4-cancel-deal يتم تغطية آليات الإلغاء
public function requestReversal(Investment $investment): void
{
    // يتم إنشاء طلب reversal
    ReversalRequest::create([
        'investment_id' => $investment->id,
        'user_id' => $investment->user_id,
        'status' => 'pending',
        'reason' => 'requested_by_user',
    ]);
    // يحتاج موافقة Admin
}
```

#### 16. الصفقة تُغلق أثناء الاستثمار
```php
// يتم التحقق داخل transaction
DB::transaction(function () use ($deal, $amount) {
    $deal = Deal::lockForUpdate()->find($deal->id);

    if (!in_array($deal->status, ['active', 'fundraising'])) {
        throw new InvestException(
            'الصفقة تم إغلاقها أو إلغاؤها أثناء معالجة طلبك',
            'DEAL_CLOSED_MID_INVESTMENT'
        );
    }
    // أكمل عملية الاستثمار...
});
```

#### 17. الحد الأدنى للاستثمار
```php
// الحد الأدنى = 10 USD أو ما يعادله بـ SYP
$minimumAmount = $deal->currency === 'USD' ? 10 : 25000; // SYP

if ($request->amount < $minimumAmount) {
    throw new InvestException(
        "الحد الأدنى للاستثمار في هذه الصفقة هو {$minimumAmount} {$deal->currency}",
        'MINIMUM_NOT_MET'
    );
}
```

## مصفوفة القرار للاستثمار

| الحالة | التصرف | HTTP Status |
|--------|--------|-------------|
| مبلغ < 10 | رفض | 422 |
| صفقة مكتملة | رفض | 400 |
| صفقة pending | رفض | 400 |
| يتجاوز المتبقي | رفض + عرض المتبقي | 422 |
| رصيد غير كافٍ | رفض | 402 |
| منشئ الصفقة | رفض | 403 |
| استثماران متزامنان | الثاني يفشل تلقائياً | 409 |
| لا محفظة للعملة | رفض + توجيه | 400 |
| محفظة موقوفة | رفض + سبب | 403 |
| Double-click | منع بـ idempotency key | - |
| صفقة تُغلق أثناء العملية | تراجع تلقائي | 409 |

## ملاحظات أمان مهمة

1. **Pessimistic Locking** إلزامي لجميع عمليات الاستثمار
2. يجب أن تكون كل عملية استثمار داخل **ACID transaction**
3. استخدام **Idempotency Key** في Header الطلب لمنع ازدواجية المعالجة
4. تسجيل **Audit Trail** لكل عملية استثمار ناجحة وفاشلة
5. تطبيق **Rate Limiting**: 5 محاولات استثمار / دقيقة للمستخدم الواحد
