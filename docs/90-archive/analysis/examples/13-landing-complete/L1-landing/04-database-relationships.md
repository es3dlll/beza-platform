# 04 - علاقات قاعدة البيانات

## مخطط العلاقات

```
┌─────────────────┐       ┌──────────────────┐
│    contacts     │       │   subscribers     │
├─────────────────┤       ├──────────────────┤
│ id (PK)         │       │ id (PK)          │
│ name            │       │ email (UNIQUE)   │
│ email           │       │ name (nullable)  │
│ phone (nullable)│       │ is_active        │
│ subject         │       │ subscribed_at    │
│ message         │       │ unsubscribed_at  │
│ is_read         │       │ source           │
│ read_at         │       └──────────────────┘
│ created_at      │
└─────────────────┘

┌──────────────────┐      ┌──────────────────┐
│ merchant_inquiries│      │  agent_inquiries  │
├──────────────────┤      ├──────────────────┤
│ id (PK)          │      │ id (PK)          │
│ company_name     │      │ company_name     │
│ contact_name     │      │ contact_name     │
│ email            │      │ email            │
│ phone            │      │ phone            │
│ business_type    │      │ city             │
│ monthly_volume   │      │ has_office       │
│ notes            │      │ notes            │
│ status           │      │ status           │
│ created_at       │      │ created_at       │
└──────────────────┘      └──────────────────┘
```

## ملاحظات

- جميع الجداول مستقلة (لا توجد علاقات FK معقدة)
- `subscribers.email` فريد — لا يمكن الاشتراك بنفس البريد مرتين
- `source` في subscribers يحدد من أين جاء المشترك (footer, hero, merchant page...)
- `status` في inquiries يمكن أن يكون: `new`, `contacted`, `qualified`, `converted`, `closed`
