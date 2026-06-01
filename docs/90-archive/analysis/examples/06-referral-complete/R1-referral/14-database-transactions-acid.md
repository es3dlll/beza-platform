# 14 - ACID + الأقفال + الـ Race Conditions

## تحديات الإحالة

### مشكلة: تسجيل دعوتين بنفس الكود

```
T1: مستخدم أ يسجل بكود X
T2: مستخدم ب يسجل بكود X (نفس اللحظة)
→ كلاهما يصبح مدعواً → مكافأة مضاعفة!
```

**الحل**: UNIQUE constraint غير كافٍ — نحتاج قفل

```php
DB::transaction(function () use ($code) {
    // قفل كود الإحالة
    ReferralCode::where('id', $code->id)->lockForUpdate()->first();

    // تحقق من أن الكود لا يزال صالحاً
    if (!$code->is_active) {
        throw new \RuntimeException('الكود غير نشط');
    }

    // تحقق من أن المستخدم ليس مدعواً بالفعل (ضمن المعاملة)
    // ...
}, attempts: 3);
```

### مشكلة: صرف المكافأة مرتين

```php
// التحقق من status = pending قبل الصرف
$reward = ReferralReward::where('id', $rewardId)
    ->where('status', 'pending')
    ->lockForUpdate()
    ->firstOrFail();
```

## التحدي الأكبر: توقيت صرف المكافأة

المكافأة تُصرف بعد أول معاملة ≥ 10 USD. يجب التأكد من:
1. المعاملة الأولى فقط هي التي تشغّل المكافأة
2. عدم تكرار الصرف

```php
// في Listener أو Service يراقب معاملات المستخدم
public function handleFirstTransaction(TransactionCompleted $event)
{
    $user = $event->sender; // أو receiver

    if ($user->referred_by && $event->transaction->amount >= 10) {
        // تحقق من عدم صرف مكافأة سابقة
        $alreadyPaid = ReferralReward::where('referred_id', $user->id)
            ->where('status', 'paid')->exists();

        if (!$alreadyPaid) {
            app(RewardService::class)->payReward($user);
        }
    }
}
```
