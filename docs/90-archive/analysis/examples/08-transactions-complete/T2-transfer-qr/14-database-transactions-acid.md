# 14 - ACID + الأقفال + الـ Race Conditions

## تحويل QR: انتهاء صلاحية الكود
مشكلة: مستخدم يمسح QR قديم منتهي الصلاحية في نفس اللحظة التي ينشئ فيها QR جديد.

```
Time  |  إنشاء QR                       |  مسح QR
------|---------------------------------|-------------------------------
T1    |  إنشاء QR مع expiry=12:05       |
T2    |                                  |  مسح QR (expiry=12:05)
T3    |  إنشاء QR جديد expiry=12:10     |
T4    |  UPDATE qr SET ... WHERE id=1   |
T5    |                                  |  نجح ← لكنه QR قديم!
```

**الحل**: `SELECT ... FOR UPDATE` + التحقق من `expires_at` ضمن المعاملة + حذف QR بعد الاستخدام.

## ACID في تحويل QR
```php
DB::transaction(function () use ($qrCode, $fromWallet, $toWallet, $amount) {
    // Consistency: تحقق أن QR لم ينتهِ ولم يُستخدم
    $qr = QrCode::where('code', $qrCode)
        ->where('used', false)
        ->where('expires_at', '>', now())
        ->lockForUpdate()
        ->firstOrFail();

    // Isolation: قفل المحفظتين
    Wallet::whereIn('id', [$fromWallet->id, $toWallet->id])
        ->orderBy('id')->lockForUpdate()->get();

    // Atomicity: خصم + إضافة + تحديث QR
    $fromWallet->decrement('balance', $amount);
    $toWallet->increment('balance', $amount);
    $qr->update(['used' => true, 'used_at' => now()]);
});
```

## Race Conditions المحددة
| السيناريو | المشكلة | الحل |
|-----------|---------|------|
| QR منتهي الصلاحية يُمسح | قبول QR قديم | التحقق من expires_at ضمن المعاملة |
| QR يُستخدم مرتين | خصم مزدوج | mark_qr_as_used + UNIQUE constraint |
| مستويان يمسحان QR معاً | سباق على نفس QR | FOR UPDATE على صف QR |
| إنشاء QR أثناء مسح QR | بيانات غير متناسقة | عزل Serializable |

## SQL الفعلي
```sql
START TRANSACTION;
SELECT * FROM qr_codes WHERE code = ? AND used = FALSE AND expires_at > NOW() FOR UPDATE;
SELECT * FROM wallets WHERE id IN (?, ?) ORDER BY id FOR UPDATE;
UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ?;
UPDATE wallets SET balance = balance + ? WHERE id = ?;
UPDATE qr_codes SET used = TRUE, used_at = NOW() WHERE id = ?;
COMMIT;
```
