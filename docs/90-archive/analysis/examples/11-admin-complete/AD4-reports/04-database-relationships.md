# 04 - علاقات الجداول للتقارير

## الاستعلامات الرئيسية

### التقرير اليومي
```sql
-- إجمالي المعاملات
SELECT COUNT(*) as total_transactions,
       COALESCE(SUM(amount), 0) as total_volume,
       COALESCE(SUM(fee), 0) as total_fees
FROM transactions
WHERE DATE(created_at) = '2026-05-27'
  AND status = 'completed';

-- المستخدمون الجدد
SELECT COUNT(*) as new_users
FROM users
WHERE DATE(created_at) = '2026-05-27'
  AND deleted_at IS NULL;

-- توزيع المعاملات حسب النوع
SELECT type, COUNT(*) as count, SUM(amount) as volume
FROM transactions
WHERE DATE(created_at) = '2026-05-27'
  AND status = 'completed'
GROUP BY type;

-- المستخدمون النشطون
SELECT COUNT(DISTINCT user_id) as active_users
FROM transactions
WHERE DATE(created_at) = '2026-05-27'
  AND status = 'completed';
```

### التقرير الشهري
```sql
-- مقارنة بالشهر السابق
SELECT
  SUM(CASE WHEN DATE_FORMAT(created_at, '%Y-%m') = '2026-05' THEN amount ELSE 0 END) as current_month,
  SUM(CASE WHEN DATE_FORMAT(created_at, '%Y-%m') = '2026-04' THEN amount ELSE 0 END) as prev_month,
  ((SUM(CASE WHEN DATE_FORMAT(created_at, '%Y-%m') = '2026-05' THEN amount ELSE 0 END) -
    SUM(CASE WHEN DATE_FORMAT(created_at, '%Y-%m') = '2026-04' THEN amount ELSE 0 END)) /
    NULLIF(SUM(CASE WHEN DATE_FORMAT(created_at, '%Y-%m') = '2026-04' THEN amount ELSE 0 END), 0) * 100
  ) as growth_percent
FROM transactions
WHERE status = 'completed'
  AND created_at >= '2026-04-01'
  AND created_at < '2026-06-01';

-- MAU (Monthly Active Users)
SELECT COUNT(DISTINCT user_id) as mau
FROM (
  SELECT from_wallet_id as user_id FROM transactions WHERE ... AND status='completed'
  UNION
  SELECT to_wallet_id FROM transactions WHERE ... AND status='completed'
) as active_users;
```

### التقرير المالي (P&L)
```sql
-- الإيرادات
SELECT type, SUM(amount) as revenue
FROM transactions
WHERE type IN ('fee', 'exchange_profit', 'merchant_commission')
  AND status = 'completed'
  AND created_at BETWEEN ? AND ?
GROUP BY type;

-- التكاليف
SELECT category, SUM(amount) as cost
FROM operational_costs
WHERE date BETWEEN ? AND ?
GROUP BY category;
```
