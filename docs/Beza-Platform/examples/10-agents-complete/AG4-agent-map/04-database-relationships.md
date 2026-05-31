# 04 - علاقات الجداول + ER (Database Relationships)

## مخطط ER (ER Diagram)
```
┌─────────────┐      ┌──────────────────┐      ┌──────────────────┐
│   Agent     │      │ AgentLocation    │      │   AgentReview    │
├─────────────┤      ├──────────────────┤      ├──────────────────┤
│ id          │──1──>│ agent_id         │      │ id               │
│ user_id     │      │ id               │<──1──│ agent_id         │
│ name        │      │ latitude         │      │ user_id          │
│ phone       │      │ longitude        │      │ rating           │
│ status      │      │ updated_at       │      │ review           │
└─────────────┘      └──────────────────┘      └──────────────────┘
```

## علاقات الجداول (Table Relationships)
### agents -> agent_locations (1:1)
- One active location per agent (UPSERT strategy)

### agents -> agent_reviews (1:M)
- Users can review agents after service

## الفهارس الرئيسية (Key Indexes)
```sql
CREATE UNIQUE INDEX idx_agent_locations_agent_id ON agent_locations(agent_id);
CREATE INDEX idx_agent_locations_coords ON agent_locations(latitude, longitude);
```
