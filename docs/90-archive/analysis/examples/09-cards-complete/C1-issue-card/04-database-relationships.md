# 04 - علاقات قاعدة البيانات (Database Relationships)

## ???? ER (ER Diagram)
```
┌─────────────┐      ┌──────────────────┐
│    User     │      │      Card        │
├─────────────┤      ├──────────────────┤
│ id          │──1──>│ user_id          │
│ name        │      │ id               │
│ phone       │      │ type (virtual|phy)│
│ email       │      │ status           │
└─────────────┘      │ last_four        │
                     │ expiry_date      │
                     │ daily_limit      │
                     │ created_at       │
                     └──────────────────┘
```

## علاقات الجداول (Table Relationships)
### users -> cards (1:M)
- Each user can have multiple cards
- Foreign key: `user_id` on `cards` table
- Cascade on delete

### cards -> transactions (1:M)
- Each card has many transactions
- Foreign key: `card_id` on `transactions` table
- Nullable (transaction may use wallet directly)

## الفهارس الرئيسية (Key Indexes)
```sql
CREATE INDEX idx_cards_user_id ON cards(user_id);
CREATE INDEX idx_cards_status ON cards(status);
CREATE INDEX idx_cards_type ON cards(type);
```
