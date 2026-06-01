# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram)

```
  ┌──────────────────┐
  │      deals        │
  │──────────────────│
  │ PK id             │
  │ title             │
  │ target_amount     │
  │ current_amount    │
  │ currency          │
  │ status (active)   │──┐
  └────────┬─────────┘  │
           │ 1           │
           │             │
           │ hasMany     │
           ▼             │
  ┌──────────────────┐   │
  │ deal_investments  │   │
  │──────────────────│   │
  │ PK id             │   │
  │ FK deal_id        │───┘
  │ FK investor_id    │──┐
  │ amount            │  │
  │ amount_in_usd     │  │
  │ status            │  │
  └──────────────────┘  │
                        │
           ┌────────────┘
           ▼
  ┌──────────────────┐
  │      users        │
  │──────────────────│
  │ PK id             │
  │ name, phone, ...  │
  └────────┬─────────┘
           │ 1
           │
           ▼
  ┌──────────────────┐
  │     wallets       │
  │──────────────────│
  │ PK id             │
  │ FK user_id        │
  │ currency          │
  │ balance           │
  │ frozen_balance    │
  └──────────────────┘
```

## استعلامات SQL الرئيسية

### التحقق من أن الصفقة نشطة وغير مكتملة
```sql
SELECT * FROM deals
WHERE id = ? AND status IN ('active', 'filled')
AND current_amount < target_amount
FOR UPDATE;
```

### تسجيل الاستثمار
```sql
INSERT INTO deal_investments (deal_id, investor_id, amount, amount_in_usd, currency, status)
VALUES (?, ?, ?, ?, ?, 'active');
```

### تحديث current_amount
```sql
UPDATE deals SET current_amount = current_amount + ?
WHERE id = ? AND (current_amount + ?) <= target_amount;
```

### خصم من محفظة المستثمر
```sql
UPDATE wallets SET balance = balance - ?
WHERE id = ? AND balance >= ? AND is_active = 1;
```
