# 19 - حالات الحافة + سيناريوهات خطأ (Edge Cases) - إتمام الصفقة وتوزيع الأرباح

## نظرة عامة

إتمام الصفقة هو أكثر العمليات تعقيداً لأنه يتضمن توزيع الأرباح على المستثمرين بدقة حسابية عالية. أي خطأ في التوزيع يمكن أن يؤدي إلى خسائر مالية أو نزاعات قانونية.

## جدول حالات الحافة

| # | الحالة | النتيجة | مستوى المعالجة | كود الخطأ |
|---|--------|---------|---------------|-----------|
| 1 | profit_actual = 0% | لا توزيع أرباح (يتم الإتمام فقط) | Business | NO_PROFIT |
| 2 | profit_actual = 0 والمستثمرون موجودون | إتمام بدون أرباح | مسموح | - |
| 3 | لا يوجد مستثمرون نشطون | رفض | Business | NO_ACTIVE_INVESTORS |
| 4 | الصفقة مكتملة مسبقاً | رفض | Business | ALREADY_COMPLETED |
| 5 | الصفقة ملغاة | رفض | Business | DEAL_CANCELLED |
| 6 | توزيع الأرباح لـ 1000+ مستثمر | بطيء لكن يعمل (DB::transaction) | Performance | - |
| 7 | محفظة مستثمر موقوفة أثناء التوزيع | تخطي و تسجيل خطأ | Service | FROZEN_WALLET_SKIPPED |
| 8 | الرصيد لا يتسع للربح | يبقى الربح مستحقاً في دفتر الأستاذ | Accounting | PENDING_LEDGER |
| 9 | إتمام صفقة بعملة SYP | تحويل لـ USD للتوزيع | Service | CURRENCY_CONVERSION |
| 10 | profit_actual > 1000 | رفض | Validation | PROFIT_EXCEEDS_LIMIT |
| 11 | إتمام صفقة بخسارة (negative profit) | توزيع الخسارة على المستثمرين | Business | NEGATIVE_PROFIT |
| 12 | إتمام الصفقة مبكراً (قبل الموعد) | مسموح مع مراجعة | Business | EARLY_COMPLETION |
| 13 | إتمام الصفقة متأخراً (بعد الموعد) | مسموح مع غرامة إن وجدت | Business | LATE_COMPLETION |
| 14 | أخطاء دقة التوزيع (Rounding errors) | استخدام أسلوب التوزيع النسبي العادل | Accounting | ROUNDING_ADJUSTMENT |
| 15 | أحد المستثمرين مجمد وقت التوزيع | إيداع الربح في حساب تعليق (Suspense) | Service | PROFIT_SUSPENDED |
| 16 | إتمام جزئي (بيع جزء من الشحنة فقط) | توزيع نسبي حسب ما تم بيعه | Business | PARTIAL_COMPLETION |

## تحليل الحالات بالتفصيل

### 1-2. ربح 0% أو صفر
```php
if ($deal->profit_actual <= 0) {
    // صفقة بدون أرباح - فقط إرجاع رأس المال
    $this->distributeCapitalOnly($deal);
    $deal->status = DealStatus::COMPLETED;
    $deal->save();
}
```

### 3. لا يوجد مستثمرون نشطون
```php
$activeInvestors = $deal->investments()
    ->whereHas('user', fn($q) => $q->where('status', 'active'))
    ->count();

if ($activeInvestors === 0) {
    throw new CompleteException(
        'لا يوجد مستثمرون نشطون في هذه الصفقة لإتمامها',
        'NO_ACTIVE_INVESTORS'
    );
}
```

### 4-5. صفقة مكتملة أو ملغاة
```php
public function canComplete(Deal $deal): void
{
    if ($deal->status === DealStatus::COMPLETED) {
        throw new CompleteException('الصفقة مكتملة مسبقاً', 'ALREADY_COMPLETED');
    }
    if ($deal->status === DealStatus::CANCELLED) {
        throw new CompleteException('الصفقة ملغاة ولا يمكن إتمامها', 'DEAL_CANCELLED');
    }
}
```

### 6. توزيع الأرباح لـ 1000+ مستثمر
```php
// استخدام chunk لتجنب مشاكل الذاكرة
public function distributeProfit(Deal $deal): void
{
    $totalInvestment = $deal->investments()->sum('amount');
    $totalProfit = $deal->profit_actual;
    $totalReturn = $totalInvestment + $totalProfit;

    DB::transaction(function () use ($deal, $totalInvestment, $totalReturn) {
        $deal->investments()->chunk(100, function ($investments) use ($totalInvestment, $totalReturn) {
            foreach ($investments as $inv) {
                $ratio = $inv->amount / $totalInvestment;
                $investorReturn = (float) bcmul((string) $totalReturn, (string) $ratio, 6);
                $investorProfit = $investorReturn - $inv->amount;

                $inv->user->wallet->increment('balance', $investorReturn);
                $inv->update(['returned_amount' => $investorReturn, 'profit' => $investorProfit]);
            }
        });
    });
}
```

### 7. محفظة مستثمر موقوفة أثناء التوزيع
```php
foreach ($investments as $inv) {
    if ($inv->user->wallet->is_frozen) {
        // تسجيل الربح كـ "مستحق" في جدول منفصل
        PendingProfit::create([
            'user_id' => $inv->user_id,
            'deal_id' => $deal->id,
            'amount' => $investorReturn,
            'reason' => 'wallet_frozen',
        ]);

        activity()
            ->causedBy($inv->user)
            ->log("تخطي توزيع ربح للمستخدم {$inv->user_id} بسبب تجميد المحفظة");
        continue;
    }
    // توزيع عادي...
}
```

### 8. الرصيد لا يتسع للربح (نادر)
```php
// في حال كان رصيد محفظة Admin لا يكفي لتغطية الأرباح
if ($adminWallet->balance < $totalProfit) {
    // يتم تسجيل الربح كذمة مالية في دفتر الأستاذ
    LedgerEntry::create([
        'type' => 'profit_payable',
        'deal_id' => $deal->id,
        'amount' => $totalProfit,
        'status' => 'unpaid',
    ]);
    // وإرسال إشعار لـ Admin بتعبئة الرصيد
    NotificationService::notifyAdmin('رصيد غير كافٍ لتوزيع أرباح صفقة ' . $deal->title);
}
```

### 9. إتمام صفقة بعملة SYP
```php
// التحويل من SYP إلى USD بسعر الصرف الحالي
if ($deal->currency === 'SYP') {
    $exchangeRate = ExchangeRate::where('from', 'SYP')
        ->where('to', 'USD')
        ->latest()
        ->first()
        ->rate;

    $totalInUSD = $deal->current_amount / $exchangeRate;
    // التوزيع يتم بالعملة الأصلية (SYP) لكن الحسابات تكون بالـ USD
}
```

### 10. profit_actual > 1000 (آلاف الدولارات)
```php
// رفض الأرباح الخيالية كإجراء أمان
if ($deal->profit_actual > 1000) {
    // ملاحظة: الرقم 1000 هنا افتراضي، القيمة الفعلية تعتمد على target_amount
    $maxProfit = $deal->target_amount * 2; // حد أقصى 200% ربح
    if ($deal->profit_actual > $maxProfit) {
        throw new CompleteException(
            'نسبة الربح تتجاوز الحد المسموح به (200% من رأس المال)',
            'PROFIT_EXCEEDS_LIMIT'
        );
    }
}
```

### 11. خسارة (ربح سالب)
```php
// توزيع الخسارة نسبياً على المستثمرين
public function distributeLoss(Deal $deal, float $lossAmount): void
{
    DB::transaction(function () use ($deal, $lossAmount) {
        $totalInvested = $deal->investments()->sum('amount');
        $recoveryAmount = $totalInvested - abs($lossAmount); // المبلغ المسترجع

        $deal->investments()->chunk(100, function ($investments) use ($totalInvested, $recoveryAmount) {
            foreach ($investments as $inv) {
                $ratio = $inv->amount / $totalInvested;
                $returnAmount = (float) bcmul((string) $recoveryAmount, (string) $ratio, 6);
                $loss = $inv->amount - $returnAmount;

                $inv->user->wallet->increment('balance', $returnAmount);
                $inv->update([
                    'returned_amount' => $returnAmount,
                    'profit' => -$loss,
                    'is_loss' => true,
                ]);

                // إشعار المستثمر بالخسارة
                NotificationService::notifyUser(
                    $inv->user,
                    "تم إتمام الصفقة {$deal->title} بخسارة قدرها \${$loss}"
                );
            }
        });
    });
}
```

### 12-13. إتمام مبكر أو متأخر
```php
$today = now();
$expectedEnd = $deal->end_date;

if ($today->lt($expectedEnd)) {
    // إتمام مبكر - قد يكون بسبب بيع الشحنة بسرعة
    Log::info("إتمام مبكر للصفقة {$deal->id} قبل {$today->diffInDays($expectedEnd)} يوماً");
}

if ($today->gt($expectedEnd)) {
    // إتمام متأخر - تطبيق غرامة تأخير إن وجدت
    $lateDays = $expectedEnd->diffInDays($today);
    if ($deal->late_fee_percent > 0) {
        $lateFee = $deal->target_amount * ($deal->late_fee_percent / 100) * $lateDays;
        $deal->profit_actual -= $lateFee; // خصم غرامة التأخير من الربح
    }
}
```

### 14. أخطاء دقة التوزيع (Rounding)
```php
// مشكلة: 100.01 / 3 = 33.336666... لكل مستثمر
// الحل: توزيع الفروق الصغيرة
public function fairDistribution(Collection $investments, float $totalReturn): array
{
    $totalInvested = $investments->sum('amount');
    $distribution = [];
    $distributedSoFar = 0;

    foreach ($investments as $i => $inv) {
        $ratio = $inv->amount / $totalInvested;
        if ($i === $investments->count() - 1) {
            // آخر مستثمر يحصل على الفارق لضمان الدقة
            $share = $totalReturn - $distributedSoFar;
        } else {
            $share = floor($ratio * $totalReturn * 100) / 100; // تقريب لأسفل
        }
        $distribution[$inv->id] = $share;
        $distributedSoFar += $share;
    }
    return $distribution;
}
```

### 15. مستثمر مجمد وقت التوزيع
```php
// بدلاً من التخطي، نستخدم حساب تعليق (Suspense Account)
if ($investor->wallet->is_frozen) {
    SuspenseAccount::create([
        'user_id' => $investor->id,
        'deal_id' => $deal->id,
        'amount' => $investorShare,
        'type' => 'profit_hold',
        'release_on' => 'wallet_unfrozen',
    ]);
}
```

### 16. إتمام جزئي
```php
// بيع جزء من الشحنة فقط - توزيع نسبي
public function partialComplete(Deal $deal, float $soldAmount): void
{
    $soldPercent = $soldAmount / $deal->total_shipment_value;
    $partialProfit = $deal->estimated_profit * $soldPercent;

    // توزيع الربح الجزئي
    $this->distributeProfitWithAmount($deal, $soldAmount, $partialProfit);

    // تحديث حالة الصفقة إلى "partially_completed"
    $deal->status = DealStatus::PARTIALLY_COMPLETED;
    $deal->sold_amount = $soldAmount;
    $deal->save();
}
```

## مصفوفة القرار للإتمام

| الحالة | هل يُسمح بالإتمام؟ | آلية التوزيع | إشعار للمستثمرين |
|--------|-------------------|--------------|------------------|
| ربح 0% | نعم | رأس المال فقط | نعم |
| خسارة | نعم | خصم نسبي من رأس المال | نعم + اعتذار |
| إتمام مبكر | نعم | توزيع كامل | نعم |
| إتمام متأخر | نعم (مع غرامة) | توزيع بعد خصم الغرامة | نعم |
| محفظة مجمدة | نعم (يتخطى) | حساب تعليق | نعم (للمستخدم) |
| جزئي | نعم | توزيع نسبي | نعم |
| 1000+ مستثمر | نعم (مع chunk) | توزيع عادي (بطيء) | نعم (مجمع) |
| خطأ تقريب | تلقائي | توزيع الفرق على آخر مستثمر | لا |

## توصيات

1. استخدام **bcmath** (binary calculator) للعمليات الحسابية لتجنب أخطاء الفاصلة العائمة
2. إضافة **جولة اختبارية (Dry Run)** قبل الإتمام الفعلي لعرض نتائج التوزيع
3. تسجيل **Audit Log** كامل لعملية التوزيع (من حصل على كم ومتى)
4. في حال فشل التوزيع لمنتصف الطريق، يجب أن يكون هناك **Rollback كامل**
5. إضافة **فترة سماح (Grace Period)** للصفقات المتأخرة قبل تطبيق الغرامات
