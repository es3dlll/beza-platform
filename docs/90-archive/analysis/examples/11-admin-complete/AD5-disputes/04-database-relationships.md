# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram)

```
┌──────────────────────┐
│     transactions     │
│──────────────────────│
│ id (PK)              │
│ from_wallet_id       │
│ to_wallet_id         │
│ amount               │
│ status               │
│ type                 │
└──────────┬───────────┘
           │ 1
           │
           ▼
┌──────────────────────┐        ┌──────────────────────────────┐
│       disputes       │        │      dispute_evidence        │
│──────────────────────│        │──────────────────────────────│
│ id (PK)              │1      N│ id (PK)                      │
│ transaction_id (FK)  │────────│ dispute_id (FK)              │
│ complainant_id (FK)  │        │ file_path                    │
│ respondent_id (FK)   │        │ type (image,document,other)  │
│ reason               │        │ original_name                │
│ description          │        │ created_at                   │
│ status (open,        │        └──────────────────────────────┘
│   investigating,     │
│   resolved, rejected)│
│ resolution           │
│ resolved_by          │
│ resolved_at          │
│ auto_closed_at       │
│ created_at           │
└──────────────────────┘
```

## الاستعلامات الرئيسية

```sql
-- النزاعات المفتوحة
SELECT d.*, t.reference_number, t.amount,
       cu.name as complainant_name, cu.phone as complainant_phone,
       ru.name as respondent_name
FROM disputes d
JOIN transactions t ON t.id = d.transaction_id
JOIN users cu ON cu.id = d.complainant_id
JOIN users ru ON ru.id = d.respondent_id
WHERE d.status IN ('open', 'investigating')
ORDER BY d.created_at DESC;

-- Refund (استرجاع)
START TRANSACTION;
UPDATE disputes SET status = 'resolved', resolution = 'refund',
       resolved_by = ?, resolved_at = NOW()
WHERE id = ?;

UPDATE wallets SET balance = balance + ?
WHERE id = (SELECT to_wallet_id FROM transactions WHERE id = ?);

UPDATE wallets SET balance = balance - ?
WHERE id = (SELECT from_wallet_id FROM transactions WHERE id = ?);

UPDATE transactions SET status = 'refunded'
WHERE id = ?;
COMMIT;
```
