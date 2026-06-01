# 04 - علاقات الجداول لإدارة المستخدمين

## مخطط ER (ER Diagram)

```
┌──────────────────────┐
│        users         │
│──────────────────────│
│ PK id                │
│ uuid (unique)        │
│ name                 │
│ phone (unique)       │
│ email (unique)       │
│ password             │
│ pin_code             │
│ status (active,      │
│   suspended, blocked)│
│ kyc_status           │
│ is_admin             │
│ is_merchant          │
│ is_agent             │
│ last_login_at        │
│ last_login_ip        │
│ device_id            │
│ fcm_token            │
│ deleted_at (soft)    │
│ created_at           │
└──────┬───────────────┘
       │
       ├── hasMany: Wallet (user_id) → CASCADE
       ├── hasMany: Merchant (user_id) → CASCADE
       ├── hasMany: Agent (user_id) → CASCADE
       ├── hasManyThrough: Transaction (via Wallet) → SET NULL
       └── hasMany: ActivityLog (user_id) → CASCADE
```

## الاستعلامات الرئيسية

### قائمة المستخدمين مع فلترة
```sql
SELECT id, uuid, name, phone, email, status, kyc_status,
       is_admin, is_merchant, is_agent, last_login_at, created_at
FROM users
WHERE deleted_at IS NULL
  AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)
  AND (status = ? OR ? IS NULL)
  AND (kyc_status = ? OR ? IS NULL)
ORDER BY created_at DESC
LIMIT 20 OFFSET ?;
```

### تفاصيل مستخدم مع المحافظ
```sql
SELECT * FROM users WHERE id = ? AND deleted_at IS NULL;

SELECT * FROM wallets WHERE user_id = ?;

SELECT * FROM transactions
WHERE from_wallet_id IN (SELECT id FROM wallets WHERE user_id = ?)
   OR to_wallet_id IN (SELECT id FROM wallets WHERE user_id = ?)
ORDER BY created_at DESC LIMIT 20;
```

### تعليق مستخدم
```sql
UPDATE users SET status = 'suspended', updated_at = NOW()
WHERE id = ? AND is_admin = 0 AND deleted_at IS NULL;
```

### حذف ناعم
```sql
UPDATE users SET deleted_at = NOW() WHERE id = ? AND is_admin = 0;
-- المعاملات تبقى مع SET NULL على from_wallet_id/to_wallet_id
```
