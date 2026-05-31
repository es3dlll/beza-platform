# 04 - علاقات الجداول للوحة التحكم (Database Relationships)

## مخطط ER (ER Diagram) (Entity-Relationship)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    ER Diagram — Admin Dashboard Stats                       │
└─────────────────────────────────────────────────────────────────────────────┘

  ┌──────────────────────┐       ┌──────────────────────┐
  │        users         │       │        users         │
  │──────────────────────│       │──────────────────────│
  │ id (PK)              │       │ id (PK)              │
  │ name                 │       │ name                 │
  │ phone (UQ)           │       │ phone (UQ)           │
  │ email (UQ)           │1      │ email (UQ)           │1
  │ status               │───────│ status               │───────
  │ is_admin             │       │ is_admin             │       │
  │ is_merchant          │       │ is_merchant          │       │
  │ is_agent             │       │ is_agent             │       │
  │ last_login_at        │       │ last_login_at        │       │
  │ kyc_status           │       │ kyc_status           │       │
  │ created_at           │       │ created_at           │       │
  └──────────┬───────────┘       └──────────────────────┘       │
             │ hasMany                                         │ hasMany
             ▼                                                  ▼
  ┌──────────────────────┐       ┌──────────────────────┐
  │       wallets        │       │     transactions     │
  │──────────────────────│       │──────────────────────│
  │ id (PK)              │       │ id (PK)              │
  │ user_id (FK)         │       │ from_wallet_id (FK)  │───
  │ currency             │       │ to_wallet_id (FK)    │───│
  │ balance              │       │ amount               │   │
  │ frozen_balance       │       │ type                 │   │
  │ is_active            │       │ status               │   │
  └──────────────────────┘       │ created_at           │   │
                                 └──────────────────────┘   │
                                                            │
  ┌──────────────────────┐       ┌──────────────────────┐   │
  │      merchants       │       │       agents         │   │
  │──────────────────────│       │──────────────────────│   │
  │ id (PK)              │       │ id (PK)              │   │
  │ user_id (FK)         │       │ user_id (FK)         │   │
  │ business_name        │       │ office_name          │   │
  │ status               │       │ status               │   │
  │ total_transactions   │       │ total_transactions   │   │
  │ total_volume         │       │ total_volume         │   │
  │ created_at           │       │ created_at           │   │
  └──────────────────────┘       └──────────────────────┘
```

## الاستعلامات الرئيسية (Key Queries)

### إجمالي المستخدمين
```sql
SELECT COUNT(*) as total_users, 
       SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
       SUM(CASE WHEN is_merchant = 1 THEN 1 ELSE 0 END) as merchants_count,
       SUM(CASE WHEN is_agent = 1 THEN 1 ELSE 0 END) as agents_count
FROM users WHERE deleted_at IS NULL;
```

### إحصائيات المعاملات
```sql
SELECT COUNT(*) as total_transactions,
       COALESCE(SUM(amount), 0) as transaction_volume,
       COALESCE(SUM(CASE WHEN type = 'fee' THEN amount ELSE 0 END), 0) as total_fees
FROM transactions WHERE status = 'completed';
```

### إجمالي أرصدة المحافظ
```sql
SELECT currency, COALESCE(SUM(balance), 0) as total_balance,
       COALESCE(SUM(frozen_balance), 0) as total_frozen
FROM wallets WHERE is_active = 1
GROUP BY currency;
```

### المستخدمون النشطون يومياً (آخر 30 يوم)
```sql
SELECT DATE(created_at) as date, COUNT(DISTINCT user_id) as active_users
FROM transactions
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date;
```

### أعلى 5 تجار حسب حجم المعاملات
```sql
SELECT u.id, u.name, u.phone, m.business_name,
       m.total_transactions, m.total_volume
FROM merchants m
JOIN users u ON u.id = m.user_id
WHERE m.status = 'active'
ORDER BY m.total_volume DESC
LIMIT 5;
```
