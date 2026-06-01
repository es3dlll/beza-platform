# 02 - البنية المعمارية - إدارة البطاقة (Card Management)

```
┌───────────────────────────────────────────────────────┐
│ Flutter / React                                        │
│ [CardManageScreen] → [PATCH /api/v1/cards/{id}]       │
└──────────────────────┬────────────────────────────────┘
                       ▼
┌───────────────────────────────────────────────────────┐
│ Laravel Middleware                                     │
│ auth:api → throttle → card.owner                  │
└──────────────────────┬────────────────────────────────┘
                       ▼
┌───────────────────────────────────────────────────────┐
│ CardManageController                                   │
│ 1. Find card (or fail)                                 │
│ 2. Authorize ownership                                 │
│ 3. Validate (UpdateCardRequest)                        │
│ 4. Route to correct handler based on action:           │
│    ├── changeStatus() → CardStatusService              │
│    ├── changePin() → PinChangeService                  │
│    └── updateLimit() → LimitUpdateService              │
│ 5. Return response                                     │
└──────────────────────┬────────────────────────────────┘
                       ▼
┌───────────────────────────────────────────────────────┐
│ CardManageService                                      │
│ 1. DB::transaction {                                   │
│    ├── lockForUpdate(card)                             │
│    ├── Validate transition rules                       │
│    ├── Update card fields                              │
│    ├── Insert audit log                                │
│    └── Wallet adjustments if needed                    │
│    }                                                   │
│ 2. Dispatch CardStatusChanged event                    │
└──────────────────────┬────────────────────────────────┘
                       │
                       ▼
                 ┌──────────┐
                 │  MySQL   │
                 │  cards   │
                 │  audits  │
                 └──────────┘
```

## مسارات API للإدارة

| المسار | الفعل |
|--------|-------|
| PATCH /api/v1/cards/{id}/status | تغيير حالة البطاقة |
| PATCH /api/v1/cards/{id}/pin | تغيير رمز PIN |
| PATCH /api/v1/cards/{id}/limit | تحديث الحدود |
