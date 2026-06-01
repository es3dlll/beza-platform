# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram)

```
┌──────────────────────┐
│        users         │
│──────────────────────│
│ id (PK)              │
│ name                 │
│ phone                │
│ is_merchant          │
│ is_agent             │
│ kyc_status           │
└──────┬───────────────┘
       │ 1
       │
       ├── hasOne: Merchant
       ├── hasOne: Agent
       │
       ▼
┌──────────────────────┐      ┌──────────────────────────────┐
│      merchants       │      │      merchant_documents      │
│──────────────────────│      │──────────────────────────────│
│ id (PK)              │1    N│ id (PK)                      │
│ user_id (FK) (UQ)    │──────│ merchant_id (FK)             │
│ business_name        │      │ type (commercial_reg,       │
│ business_type        │      │       tax_card, id_photo,   │
│ commercial_reg_no    │      │       license, contract)    │
│ tax_card_no          │      │ file_path                   │
│ address              │      │ status (pending,approved,   │
│ website              │      │         rejected)           │
│ description          │      │ notes                       │
│ status (pending,     │      │ created_at                  │
│   active, rejected,  │      └──────────────────────────────┘
│   suspended)         │
│ rejection_reason     │
│ reviewed_by          │
│ reviewed_at          │
│ created_at           │
│ total_transactions   │
│ total_volume         │
└──────────────────────┘

┌──────────────────────┐
│       agents         │
│──────────────────────│
│ id (PK)              │
│ user_id (FK) (UQ)    │
│ office_name          │
│ license_number       │
│ address              │
│ service_areas        │
│ status (pending,     │
│   active, rejected,  │
│   suspended)         │
│ rejection_reason     │
│ reviewed_by          │
│ reviewed_at          │
│ created_at           │
│ total_transactions   │
│ total_commission     │
└──────────────────────┘
```

## الاستعلامات الرئيسية

### قائمة طلبات التجار المعلقة
```sql
SELECT m.*, u.name, u.phone, u.email, u.kyc_status
FROM merchants m
JOIN users u ON u.id = m.user_id
WHERE m.status = 'pending'
ORDER BY m.created_at DESC;
```

### الموافقة على تاجر (Atomic)
```sql
START TRANSACTION;
UPDATE merchants SET status = 'active', reviewed_by = ?, reviewed_at = NOW()
WHERE id = ? AND status = 'pending';
UPDATE users SET is_merchant = 1 WHERE id = ?;
COMMIT;
```

### رفض تاجر
```sql
UPDATE merchants
SET status = 'rejected', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW()
WHERE id = ? AND status = 'pending';
```
