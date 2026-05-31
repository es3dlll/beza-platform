# 19 - حالات الحافة + سيناريوهات خطأ (Edge Cases) - نظام الإحالة

## نظرة عامة

نظام الإحالة هو محرك نمو المنصة، لكنه عرضة للاستغلال. يجب التعامل بحذر مع حالات مثل الإحالة الذاتية، الاحتيال عبر IP واحد، ومحاولات صرف المكافأة المكررة.

## جدول حالات الحافة

| # | الحالة | النتيجة | مستوى المعالجة | كود الخطأ |
|---|--------|---------|---------------|-----------|
| 1 | مستخدم يدعو نفسه | رفض | Business | SELF_REFERRAL |
| 2 | مستخدم مدعو مسبقاً يحاول claim مرة أخرى | رفض | Business | ALREADY_REFERRED |
| 3 | كود إحالة غير موجود | رفض 422 | Validation | INVALID_CODE |
| 4 | كود إحالة من مستخدم غير نشط | رفض | Service | REFERRER_INACTIVE |
| 5 | مكافأة لصديق لم يقم بأول معاملة | تبقى pending | Business | REWARD_PENDING |
| 6 | أول معاملة للصديق أقل من 10 USD | لا تصرف المكافأة | Business | MINIMUM_NOT_MET |
| 7 | إنشاء كود إحالة متكرر (نفس المستخدم) | إرجاع الكود الموجود | Service | CODE_EXISTS |
| 8 | مكافأة لمستخدم محظور | إيداع في المحفظة (يمكن سحبها لاحقاً) | Service | USER_BANNED |
| 9 | منافسة على نفس كود الإحالة (مستخدمين بنفس الوقت) | الأول ينجح والثاني يفشل | ACID | CONCURRENT_CLAIM |
| 10 | صرف المكافأة مرتين (Double reward) | منع عبر status=pending + قفل | ACID | DOUBLE_REWARD |
| 11 | إحالة ذاتية (تسجيل بكود الإحالة الخاص) | رفض + تسجيل محاولة احتيال | Security | SELF_REFERRAL_ATTEMPT |
| 12 | إحالات متعددة من نفس الجهاز/IP | وضع علامة اشتباه (flag for review) | Fraud Detection | SUSPICIOUS_IP |
| 13 | المدعو لا يقوم بأي إيداع أبداً | تبقى المكافأة pending للأبد (أو تنتهي بعد 90 يوماً) | Business | REWARD_EXPIRED |
| 14 | المدعو يودع ثم يسحب فوراً | اعتبارها محاولة احتيال - إلغاء المكافأة | Fraud Detection | WITHDRAW_AFTER_DEPOSIT |
| 15 | الحد الأدنى لمكافأة الإحالة لم يتحقق | لا تصرف | Business | THRESHOLD_NOT_MET |
| 16 | عملة المكافأة (USD أو SYP) | تصرف بنفس عملة محفظة المستخدم | Service | REWARD_CURRENCY |
| 17 | كود إحالة منتهي الصلاحية | رفض + اقتراح كود جديد | Business | CODE_EXPIRED |

## تحليل الحالات بالتفصيل مع أكواد PHP

### 1-2. إحالة ذاتية أو مكررة
```php
public function claimReferral(string $code, User $newUser): void
{
    $referral = ReferralCode::where('code', $code)->firstOrFail();

    // منع الإحالة الذاتية
    if ($referral->user_id === $newUser->id) {
        activity()
            ->causedBy($newUser)
            ->withProperties(['code' => $code])
            ->log('محاولة إحالة ذاتية');

        throw new ReferralException(
            'لا يمكنك استخدام كود الإحالة الخاص بك',
            'SELF_REFERRAL'
        );
    }

    // منع الإحالة المكررة
    $alreadyReferred = ReferralReward::where('referred_user_id', $newUser->id)->exists();
    if ($alreadyReferred) {
        throw new ReferralException(
            'لقد تمت إحالتك مسبقاً بواسطة مستخدم آخر',
            'ALREADY_REFERRED'
        );
    }
}
```

### 3. كود إحالة غير موجود
```php
public function rules(): array
{
    return [
        'referral_code' => [
            'required',
            'string',
            'size:8', // أكواد الإحالة بطول 8 أحرف
            function ($attribute, $value, $fail) {
                if (!ReferralCode::where('code', $value)->exists()) {
                    $fail('كود الإحالة غير صحيح أو غير موجود');
                }
            },
        ],
    ];
}
```

### 4. كود من مستخدم غير نشط
```php
$referrer = $referral->user;
if ($referrer->status !== 'active') {
    // نسمح بتسجيل الإحالة لكن نبقي المكافأة معلقة
    Log::warning("إحالة من مستخدم غير نشط: {$referrer->id}");
    // لا نمنع التسجيل، لكن نضع علامة للمراجعة
}
```

### 5-6. المكافأة pending
```php
public function checkRewardEligibility(ReferralReward $reward): bool
{
    $referredUser = User::find($reward->referred_user_id);

    // تحقق من أول معاملة
    $firstTransaction = $referredUser->transactions()
        ->where('type', 'deposit')
        ->where('status', 'completed')
        ->first();

    if (!$firstTransaction) {
        $reward->update(['status' => 'pending', 'notes' => 'لا توجد معاملة بعد']);
        return false;
    }

    // تحقق من الحد الأدنى (10 USD)
    $minAmount = 10;
    if ($firstTransaction->amount < $minAmount) {
        $reward->update([
            'status' => 'pending',
            'notes' => "المعاملة الأولى أقل من {$minAmount} USD",
        ]);
        return false;
    }

    // المكافأة مستحقة
    $this->releaseReward($reward);
    return true;
}
```

### 7. إنشاء كود مكرر
```php
public function generateCode(User $user): string
{
    $existing = ReferralCode::where('user_id', $user->id)->first();
    if ($existing) {
        return $existing->code; // إرجاع الكود الموجود
    }

    // إنشاء كود جديد
    do {
        $code = strtoupper(Str::random(8));
    } while (ReferralCode::where('code', $code)->exists());

    ReferralCode::create([
        'user_id' => $user->id,
        'code' => $code,
    ]);

    return $code;
}
```

### 8. مكافأة لمستخدم محظور
```php
// حتى المحظورين يستحقون المكافأة
// المبلغ يودع في المحفظة ويمكن سحبه لاحقاً
$wallet = $reward->user->wallets()
    ->where('currency', $reward->currency)
    ->firstOrCreate([...]);

$wallet->increment('balance', $reward->amount);
```

### 9-10. منافسة على نفس الكود + Double reward prevention
```php
// استخدام optimistic locking مع unique index
public function claimReferralCode(string $code, User $user): ReferralReward
{
    return DB::transaction(function () use ($code, $user) {
        $referralCode = ReferralCode::where('code', $code)
            ->lockForUpdate()
            ->firstOrFail();

        // محاولة إنشاء المكافأة - الـ unique constraint سيمنع التكرار
        try {
            $reward = ReferralReward::create([
                'referral_code_id' => $referralCode->id,
                'referred_user_id' => $user->id,
                'referrer_user_id' => $referralCode->user_id,
                'amount' => config('referral.reward_amount'), // 5 USD
                'currency' => 'USD',
                'status' => 'pending',
            ]);

            return $reward;

        } catch (QueryException $e) {
            if ($e->getCode() === '23000') { // Duplicate entry
                throw new ReferralException(
                    'تم استخدام كود الإحالة هذا مسبقاً',
                    'CONCURRENT_CLAIM'
                );
            }
            throw $e;
        }
    });
}

// في ملف migration - unique constraint
// $table->unique('referred_user_id'); // مستخدم واحد فقط لكل إحالة
```

### 11. إحالة ذاتية (احتيال)
```php
// كشف متقدم للإحالة الذاتية
public function detectSelfReferralFraud(User $user): void
{
    // 1. IP match
    $registrationIp = $user->ip_address;
    $referrerIp = $user->referredBy?->ip_address;

    if ($registrationIp === $referrerIp) {
        $this->flagForFraudReview($user, 'SAME_IP_SELF_REFERRAL');
    }

    // 2. Device fingerprint match
    if ($user->device_fingerprint === $user->referredBy?->device_fingerprint) {
        $this->flagForFraudReview($user, 'SAME_DEVICE_SELF_REFERRAL');
    }
}
```

### 12. إحالات متعددة من نفس IP
```php
public function checkMultipleReferralsFromSameIP(string $ip): void
{
    $count = User::where('ip_address', $ip)
        ->where('created_at', '>=', now()->subDay())
        ->count();

    if ($count > 5) {
        // وضع علامة احتيال
        FraudAlert::create([
            'type' => 'MULTIPLE_REFERRALS_SAME_IP',
            'ip_address' => $ip,
            'user_count' => $count,
            'status' => 'pending_review',
        ]);

        Log::warning("إحالات متعددة من نفس IP: {$ip} ({$count} مستخدم)");
    }
}
```

### 13. المدعو لا يقوم بأي إيداع
```php
// CronJob شهري لتنظيف المكافآت المنتهية
Schedule::daily()->call(function () {
    $expiredRewards = ReferralReward::where('status', 'pending')
        ->where('created_at', '<', now()->subDays(90))
        ->get();

    foreach ($expiredRewards as $reward) {
        $reward->update([
            'status' => 'expired',
            'notes' => 'انتهت صلاحية المكافأة بعد 90 يوماً دون إيداع',
        ]);
    }
});
```

### 14. المدعو يودع ثم يسحب فوراً
```php
public function checkSuspiciousWithdrawal(Transaction $withdrawal): void
{
    if ($withdrawal->type !== 'withdrawal') return;

    $user = $withdrawal->user;
    $firstDeposit = $user->transactions()
        ->where('type', 'deposit')
        ->where('status', 'completed')
        ->orderBy('created_at')
        ->first();

    if (!$firstDeposit) return;

    // إذا كان السحب خلال 24 ساعة من أول إيداع
    if ($withdrawal->created_at->diffInHours($firstDeposit->created_at) < 24) {
        // إلغاء المكافأة
        $reward = ReferralReward::where('referred_user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($reward) {
            $reward->update([
                'status' => 'cancelled',
                'notes' => 'إيداع وهمي - تم السحب فوراً خلال 24 ساعة',
            ]);
        }
    }
}
```

### 15. الحد الأدنى للمكافأة
```php
public function calculateReward(Referral $referral): float
{
    $baseReward = 5.00; // 5 USD

    // مكافآت إضافية حسب حجم أول إيداع
    $firstDeposit = $referral->referredUser->transactions()
        ->where('type', 'deposit')
        ->where('status', 'completed')
        ->first();

    $depositAmount = $firstDeposit?->amount ?? 0;

    // مكافآت متدرجة
    return match (true) {
        $depositAmount >= 1000 => $baseReward + 20, // +20 USD
        $depositAmount >= 500  => $baseReward + 10, // +10 USD
        $depositAmount >= 100  => $baseReward + 5,  // +5 USD
        $depositAmount >= 10   => $baseReward,       // 5 USD فقط
        default                => 0,                 // لا مكافأة
    };
}
```

### 16. عملة المكافأة
```php
public function determineRewardCurrency(User $user): string
{
    // تفضل العملة التي يملك فيها المستخدم محفظة
    $wallets = $user->wallets()->pluck('currency')->toArray();

    if (in_array('USD', $wallets)) {
        return 'USD';
    }
    if (in_array('SYP', $wallets)) {
        return 'SYP';
    }

    // افتراضياً USD
    return 'USD';
}

public function convertReward(float $amount, string $fromCurrency, string $toCurrency): float
{
    if ($fromCurrency === $toCurrency) return $amount;

    $rate = ExchangeRate::where('from', $fromCurrency)
        ->where('to', $toCurrency)
        ->latest()
        ->first()
        ->rate;

    return $amount * $rate;
}
```

### 17. كود إحالة منتهي الصلاحية
```php
public function validateCodeNotExpired(ReferralCode $code): void
{
    $expiresAt = $code->created_at->addDays(config('referral.code_validity_days', 365));

    if (now()->gt($expiresAt)) {
        // إنشاء كود جديد تلقائياً
        $newCode = $this->generateCode($code->user);

        throw new ReferralException(
            'كود الإحالة منتهي الصلاحية. تم إنشاء كود جديد لك: ' . $newCode,
            'CODE_EXPIRED',
            ['new_code' => $newCode]
        );
    }
}
```

## مصفوفة القرار للإحالات

| الحالة | هل يُسمح؟ | المكافأة | إجراء إضافي |
|--------|-----------|----------|-------------|
| إحالة ذاتية | لا | - | تسجيل محاولة احتيال |
| كود مكرر لمستخدم | نعم (يعيد الكود) | - | - |
| إحالة من نفس IP | نعم (مع مراقبة) | pending | فحص يدوي |
| لا إيداع بعد 90 يوماً | - | تنتهي المكافأة | حذف تلقائي |
| سحب فوري بعد إيداع | - | تُلغى المكافأة | علامة احتيال |
| كود منتهي | لا (يُقترح جديد) | - | إنشاء كود بديل |
| عملة مختلفة | نعم | تحويل بسعر الصرف | تسجيل تحويل |

## توصيات أمنية

1. استخدام **Device Fingerprinting** لكشف الإحالات الوهمية
2. تطبيق **حد أقصى للإحالات اليومية** (50 إحالة / يوم للمستخدم الواحد)
3. إضافة **Captcha** عند تسجيل الدخول باستخدام كود إحالة
4. مراجعة يدوية للمكافآت التي تزيد عن 100 USD
5. تسجيل كل محاولة استخدام كود إحالة في Audit Log
6. إرسال إشعار للداعِي عند نجاح إحالة جديدة
7. تطبيق **Cooling Period** (فترة تبريد) للإحالات من نفس IP
