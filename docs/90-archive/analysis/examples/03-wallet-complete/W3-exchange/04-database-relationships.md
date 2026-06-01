# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram) — Exchange Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       ER Diagram — Exchange Flow                            │
└─────────────────────────────────────────────────────────────────────────────┘

  ┌──────────────────┐
  │      users       │──────────────────────────────────────┐
  │──────────────────│                                      │
  │ PK id            │                                      │
  │ uuid (unique)    │                                      │
  │ name             │                                      │
  │ phone (unique)   │                                      │
  │ ...              │                                      │
  └──────────────────┘                                      │
           │ 1                                               │
           │ hasMany                                        │
           ▼                                                │
  ┌──────────────────┐       ┌──────────────────┐           │
  │  Wallet (SYP)    │       │  Wallet (USD)    │           │
  │──────────────────│       │──────────────────│           │
  │ PK id            │       │ PK id            │           │
  │ FK user_id       │       │ FK user_id       │           │
  │ currency: SYP    │       │ currency: USD    │           │
  │ balance          │       │ balance          │           │
  │ wallet_number    │       │ wallet_number    │           │
  └────────┬─────────┘       └────────┬─────────┘           │
           │                         │                      │
           │ from_wallet_id          │ to_wallet_id         │
           └──────────┬──────────────┘                      │
                      ▼                                     │
             ┌──────────────────────────────┐               │
             │        transactions          │               │
             │──────────────────────────────│               │
             │ PK id                        │               │
             │ FK from_wallet_id            │───────────────┘
             │ FK to_wallet_id              │
             │ amount: المبلغ قبل التحويل    │
             │ amount_in_usd: القيمة بـ USD  │
             │ type: 'exchange'             │
             │ status: 'completed'          │
             │ reference_number (unique)    │
             │ fee: رسوم الصرافة (1.5%)     │
             │ metadata: {                  │
             │   "rate": 13000,             │
             │   "from_currency": "SYP",    │
             │   "to_currency": "USD",      │
             │   "converted_amount": 7.58,  │
             │   "fee_percentage": 1.5      │
             │ }                            │
             │ completed_at                 │
             └──────────────────────────────┘
```

## شرح العلاقات

### users → wallets (1:M)
- كل مستخدم يملك محفظة SYP + محفظة USD
- في الصرافة، نستخدم **محفظتين لنفس المستخدم**

### wallets → transactions (1:M)
- `from_wallet_id`: محفظة المصدر (التي سيتم الخصم منها)
- `to_wallet_id`: محفظة الوجهة (التي ستضاف إليها)
- type = 'exchange'

## SQL Queries المرتبطة

### خصم من محفظة المصدر
```sql
UPDATE wallets
SET balance = balance - ?  -- (amount + fee)
WHERE id = ? AND balance >= ? AND is_active = 1;
```

### إضافة للمحفظة الوجهة
```sql
UPDATE wallets
SET balance = balance + ?  -- converted amount
WHERE id = ? AND is_active = 1;
```

### تسجيل معاملة الصرافة
```sql
INSERT INTO transactions (from_wallet_id, to_wallet_id, amount, amount_in_usd,
    type, status, reference_number, description, fee, metadata, completed_at)
VALUES (?, ?, ?, ?, 'exchange', 'completed', ?, ?, ?, ?, NOW());
```

### جلب سعر الصرف
```sql
-- من جدول rates (إذا كان في DB)
SELECT rate FROM exchange_rates
WHERE from_currency = ? AND to_currency = ?
ORDER BY created_at DESC LIMIT 1;
```
