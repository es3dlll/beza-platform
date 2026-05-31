# 04 - علاقات الجداول + ER (Database Relationships)

## مخطط ER (ER Diagram)
```
┌─────────────┐      ┌──────────────────┐      ┌──────────────────┐
│    User     │      │  AgentRequest    │      │     Agent        │
├─────────────┤      ├──────────────────┤      ├──────────────────┤
│ id          │──1──>│ user_id          │      │ id               │
│ name        │      │ id               │<──1──│ user_id          │
│ phone       │      │ status           │      │ business_name    │
└─────────────┘      │ documents        │      │ commission_rate  │
                     │ admin_notes      │      │ status           │
                     │ created_at       │      │ created_at       │
                     └──────────────────┘      └──────────────────┘
```

## علاقات الجداول (Table Relationships)
### users -> agent_requests (1:M)
- User can submit multiple requests (last pending is active)
- Foreign key: `user_id` on `agent_requests`

### agent_requests -> agents (1:1)
- Approved request creates an agent record

## الفهارس الرئيسية (Key Indexes)
```sql
CREATE INDEX idx_agent_requests_user_id ON agent_requests(user_id);
CREATE INDEX idx_agent_requests_status ON agent_requests(status);
```
