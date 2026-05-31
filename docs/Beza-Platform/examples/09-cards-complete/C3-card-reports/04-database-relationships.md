# 04 - علاقات قاعدة البيانات (Database Relationships)

## مخطط ER (ER Diagram)
```
┌─────────────┐      ┌──────────────────┐      ┌──────────────────┐
│    User     │      │      Card        │      │ Transaction      │
├─────────────┤      ├──────────────────┤      ├──────────────────┤
│ id          │──1──>│ user_id          │<──1──│ card_id          │
│ name        │      │ id               │      │ id               │
└─────────────┘      │ type             │      │ amount           │
                     │ status           │      │ merchant         │
                     └──────────────────┘      │ category         │
                                               │ created_at       │
                                               └──────────────────┘
```

## علاقات الجداول (Table Relationships)
### users -> cards (1:M)
### cards -> transactions (1:M)
- All card spending is recorded as transactions

## الفهارس الرئيسية (Key Indexes)
```sql
CREATE INDEX idx_transactions_card_id ON transactions(card_id);
CREATE INDEX idx_transactions_created_at ON transactions(created_at);
CREATE INDEX idx_transactions_category ON transactions(category);
```
