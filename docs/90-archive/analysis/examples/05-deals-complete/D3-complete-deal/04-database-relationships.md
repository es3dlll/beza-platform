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
  │ expected_profit_% │
  │ profit_actual ←──│── يدخلها Admin هنا
  │ status → completed│
  └────────┬─────────┘
           │ 1
           │ hasMany
           ▼
  ┌──────────────────────────────┐
  │      deal_investments         │
  │──────────────────────────────│
  │ PK id                         │
  │ FK deal_id                    │
  │ FK investor_id                │
  │ amount → لحساب حصة الربح      │
  │ profit_earned ← بعد التوزيع   │
  │ status → completed            │
  └──────────────┬───────────────┘
                 │
                 │ لكل مستثمر
                 ▼
  ┌──────────────────┐
  │   transactions    │
  │──────────────────│
  │ type: 'investment_profit' │
  │ to_wallet_id → محفظة المستثمر │
  │ amount = حصة الربح           │
  │ reference_number: unique     │
  │ status: completed            │
  └──────────────────┘
```

## استعلام توزيع الأرباح

```sql
-- حساب إجمالي الاستثمارات
SELECT SUM(amount) as total_invested FROM deal_investments
WHERE deal_id = ? AND status = 'active';

-- لكل مستثمر: حصة الربح
SELECT
  di.id,
  di.investor_id,
  di.amount,
  di.amount / total_invested AS ratio,
  (di.amount / total_invested) * (d.target_amount * d.profit_actual / 100) AS profit_share
FROM deal_investments di
JOIN deals d ON d.id = di.deal_id
WHERE di.deal_id = ? AND di.status = 'active';
```
