# 04 - علاقات قاعدة البيانات (Database Relationships)

## مخطط ER (ER Diagram)
```
┌─────────────┐      ┌──────────────────┐      ┌──────────────────┐
│    User     │      │      Card        │      │  WalletToken     │
├─────────────┤      ├──────────────────┤      ├──────────────────┤
│ id          │──1──>│ user_id          │<──1──│ card_id          │
│ name        │      │ id               │      │ id               │
└─────────────┘      │ pan (encrypted)  │      │ device_id        │
                     │ status           │      │ device_type      │
                     └──────────────────┘      │ token (hash)     │
                                               │ status           │
                                               │ created_at       │
                                               └──────────────────┘
```

## علاقات الجداول (Table Relationships)
### cards -> wallet_tokens (1:M)
- Each card can be provisioned to multiple devices
- Foreign key: `card_id` on `wallet_tokens`

## الفهارس الرئيسية (Key Indexes)
```sql
CREATE UNIQUE INDEX idx_wallet_tokens_device_card ON wallet_tokens(device_id, card_id);
CREATE INDEX idx_wallet_tokens_token ON wallet_tokens(token);
```
