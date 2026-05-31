# 04 - علاقات الجداول + ER (Database Relationships)

## مخطط ER (ER Diagram)
```
┌─────────────┐      ┌──────────────────┐      ┌──────────────────┐
│   Agent     │      │   Settlement     │      │     Wallet       │
├─────────────┤      ├──────────────────┤      ├──────────────────┤
│ id          │──1──>│ agent_id         │      │ id               │
│ user_id     │      │ id               │      │ user_id          │
│ commission  │      │ amount           │      │ balance          │
└─────────────┘      │ fee              │      │ frozen_balance   │
                     │ status           │      └──────────────────┘
                     │ bank_account     │
                     │ requested_at     │
                     │ completed_at     │
                     └──────────────────┘
```

## علاقات الجداول (Table Relationships)
### agents -> settlements (1:M)
- Agent can request multiple settlements

### settlements -> wallets (M:1)
- Settlement debits agent's wallet

## الفهارس الرئيسية (Key Indexes)
```sql
CREATE INDEX idx_settlements_agent_id ON settlements(agent_id);
CREATE INDEX idx_settlements_status ON settlements(status);
```
