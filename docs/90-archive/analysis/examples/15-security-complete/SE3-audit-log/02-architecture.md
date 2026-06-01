# 02 - بنية نظام التدقيق (Architecture)

## موقع النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                     Laravel Application                           │
│                                                                   │
│  Controller → Service → Activity Logger → AuditLog::create()     │
│                              ↑                                    │
│                         Events/Listeners                          │
│                              ↑                                    │
│                    Observers (Model Events)                       │
│                              ↑                                    │
│                    Middleware (Requests)                          │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                         MySQL (audit_logs)                        │
│  INDEX: (event_type, created_at), (user_id), (loggable_type, id) │
│  RETENTION: 7 سنوات (أرشفة بعد سنة)                              │
└──────────────────────────────────────────────────────────────────┘
```

## طرق التسجيل

| الطريقة | أين تستخدم | مثال |
|---------|-----------|------|
| **Activity Logger** | Services | تسجيل معاملة مالية |
| **Model Observers** | Eloquent Events | تسجيل تحديث المحفظة |
| **Middleware** | HTTP Requests | تسجيل دخول المستخدم |
| **Manual** | Admin Actions | تسجيل إجراء مشرف |
