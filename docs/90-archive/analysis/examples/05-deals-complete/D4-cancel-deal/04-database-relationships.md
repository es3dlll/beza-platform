# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram)

```
  ┌──────────────────┐
  │      deals        │
  │──────────────────│
  │ PK id             │
  │ target_amount     │
  │ current_amount    │
  │ status → cancelled│
  │ cancellation_reason│
  └────────┬─────────┘
           │ 1
           │ hasMany
           ▼
  ┌──────────────────┐
  │ deal_investments  │
  │──────────────────│
  │ PK id             │
  │ FK deal_id        │
  │ FK investor_id    │
  │ amount → يرجع كاملاً│
  │ status → refunded │
  └────────┬─────────┘
           │
           ▼
  ┌──────────────────┐
  │   transactions    │
  │──────────────────│
  │ type: 'refund'    │
  │ to_wallet_id      │
  │ amount = investment│
  │ reference_number  │
  │ status: completed  │
  └──────────────────┘
```

## استعلام الاسترجاع

```sql
-- المستثمرون النشطون في الصفقة
SELECT di.id, di.investor_id, di.amount, di.amount_in_usd,
       u.id as user_id, w.id as wallet_id
FROM deal_investments di
JOIN users u ON u.id = di.investor_id
JOIN wallets w ON w.user_id = u.id AND w.currency = di.currency
WHERE di.deal_id = ? AND di.status = 'active'
FOR UPDATE;

-- إعادة المبلغ لكل مستثمر
UPDATE wallets SET balance = balance + ?
WHERE id = ? AND is_active = 1;

-- تسجيل معاملة refund لكل مستثمر
INSERT INTO transactions (to_wallet_id, amount, amount_in_usd,
    type, status, reference_number, description, completed_at)
VALUES (?, ?, ?, 'refund', 'completed', ?, ?, NOW());

-- تحديث حالة الاستثمارات
UPDATE deal_investments SET status = 'refunded'
WHERE deal_id = ? AND status = 'active';

-- تحديث حالة الصفقة
UPDATE deals SET status = 'cancelled', cancellation_reason = ?,
    cancelled_at = NOW()
WHERE id = ?;
```
