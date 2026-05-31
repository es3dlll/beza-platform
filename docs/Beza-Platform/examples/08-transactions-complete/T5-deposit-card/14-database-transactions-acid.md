# 14 - ACID + الأقفال + الـ Race Conditions

## شحن عبر البطاقة: ازدواجية طلب الشحن
مشكلة: المستخدم يضغط "شحن" مرتين بسرعة، فيتم خصم مبلغين من البطاقة المصرفية مع إضافة رصيد واحد (أو العكس).

```
Time  |  طلب 1                          |  طلب 2
------|---------------------------------|-------------------------------
T1    |  خصم 100000 من البطاقة          |
T2    |                                  |  خصم 100000 من البطاقة
T3    |  إضافة 100000 للمحفظة           |
T4    |                                  |  إضافة 100000 للمحفظة ← إجمالي +200,000!
```

**الحل**: Idempotency key (idempotency_key) لكل طلب + UNIQUE constraint.

## ACID في شحن البطاقة
```php
DB::transaction(function () use ($cardDeposit, $wallet) {
    // Atomicity: idempotency key يمنع الازدواجية
    $existing = CardDeposit::where('idempotency_key', $cardDeposit->idempotency_key)
        ->lockForUpdate()
        ->first();

    if ($existing && $existing->status === 'completed') {
        throw new DuplicateDepositException();
    }

    // خصم من البطاقة (عبر gateway خارجي + local tracking)
    $gatewayResponse = PaymentGateway::charge($cardDeposit->card_token, $cardDeposit->amount);

    // Isolation: قفل المحفظة
    Wallet::where('id', $wallet->id)->lockForUpdate()->first();

    // Atomicity: تسجيل + إضافة
    $cardDeposit->update(['status' => 'completed', 'gateway_ref' => $gatewayResponse['ref']]);
    $wallet->increment('balance', $cardDeposit->amount);
});
```

## Race Conditions المحددة
| السيناريو | المشكلة | الحل |
|-----------|---------|------|
| ضغط مزدوج على "شحن" | إضافة رصيد مرتين | Idempotency key (UUID) |
| فشل gateway بعد الخصم | خصم بدون إضافة | Saga pattern / compensation |
| استرداد (refund) مكرر | إرجاع مبلغ مرتين | UNIQUE(refund_id) |
| مهلة (timeout) من البوابة | عميل يعيد المحاولة | Retry with same idempotency_key |

## SQL الفعلي
```sql
START TRANSACTION;
SELECT * FROM card_deposits WHERE idempotency_key = ? FOR UPDATE;
SELECT * FROM wallets WHERE id = ? FOR UPDATE;
INSERT INTO card_deposits (idempotency_key, amount, status, gateway_ref) VALUES (?, ?, 'completed', ?);
UPDATE wallets SET balance = balance + ? WHERE id = ?;
COMMIT;
```
