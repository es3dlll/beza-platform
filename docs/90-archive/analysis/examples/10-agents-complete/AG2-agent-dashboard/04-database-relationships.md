# 04 - علاقات الجداول + ER (Database Relationships)

## مخطط ER (ER Diagram)
```
┌─────────────┐      ┌──────────────────┐      ┌──────────────────┐
│   Agent     │      │   AgentStat      │      │  Transaction     │
├─────────────┤      ├──────────────────┤      ├──────────────────┤
│ id          │──1──>│ agent_id         │      │ agent_id         │
│ user_id     │      │ id               │      │ id               │
│ business_name│     │ today_count      │      │ amount           │
└─────────────┘      │ today_volume     │      │ commission       │
                     │ week_volume      │      │ created_at       │
                     │ month_volume     │      └──────────────────┘
                     │ commission_total │
                     └──────────────────┘
```

## علاقات الجداول (Table Relationships)
### agents -> agent_stats (1:1)
- Each agent has one stats record, refreshed periodically

### agents -> transactions (1:M)
- Agent earns commission on each transaction

## الفهارس الرئيسية (Key Indexes)
```sql
CREATE UNIQUE INDEX idx_agent_stats_agent_id ON agent_stats(agent_id);
CREATE INDEX idx_transactions_agent_id ON transactions(agent_id);
```
