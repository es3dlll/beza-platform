# 04 - علاقات قاعدة البيانات (Database Relationships)

## مخطط ER (ER Diagram)
```
┌─────────────┐      ┌──────────────────┐      ┌──────────────────┐
│    User     │      │      Card        │      │   card_logs      │
├─────────────┤      ├──────────────────┤      ├──────────────────┤
│ id          │──1──>│ user_id          │      │ id               │
│ name        │      │ id               │<──1──│ card_id          │
└─────────────┘      │ status           │      │ action           │
                     │ daily_limit      │      │ old_status       │
                     │ frozen_at        │      │ new_status       │
                     └──────────────────┘      │ changed_by       │
                                               │ created_at       │
                                               └──────────────────┘
```

## علاقات الجداول (Table Relationships)
### users -> cards (1:M)
- Each user can have multiple cards

### cards -> card_logs (1:M)
- Every status change is logged in `card_logs`
- Foreign key: `card_id`

## الفهارس الرئيسية (Key Indexes)
```sql
CREATE INDEX idx_card_logs_card_id ON card_logs(card_id);
CREATE INDEX idx_card_logs_created_at ON card_logs(created_at);
```
