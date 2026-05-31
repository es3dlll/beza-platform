# 02 - البنية المعمارية - إصدار البطاقة (Issue Card)

```
┌───────────────────────────────────────────────────────┐
│ Flutter / React                                        │
│ [IssueCardScreen] → [IssueCardRequest]                 │
└──────────────────────┬────────────────────────────────┘
                       │ POST /api/v1/cards/issue
                       ▼
┌───────────────────────────────────────────────────────┐
│ Laravel Middleware                                     │
│ auth:api → throttle → verified                    │
└──────────────────────┬────────────────────────────────┘
                       ▼
┌───────────────────────────────────────────────────────┐
│ CardIssuanceController                                 │
│ 1. Validate (IssueCardRequest)                         │
│ 2. Call CardIssuanceService::issue()                   │
│ 3. Return CardResource                                 │
└──────────────────────┬────────────────────────────────┘
                       ▼
┌───────────────────────────────────────────────────────┐
│ CardIssuanceService                                    │
│ 1. Check user eligibility + limits                     │
│ 2. DB::transaction {                                   │
│    ├── CardNumberGenerator::generate()                 │
│    ├── Create card record + encrypt PAN                │
│    ├── Hash CVV + set expiry                           │
│    ├── WalletService::decrement() for card load        │
│    └── Insert audit log                                │
│    }                                                   │
│ 3. Dispatch CardIssued event                           │
└──────────────────────┬────────────────────────────────┘
                       │
          ┌────────────┴────────────┐
          ▼                         ▼
    ┌──────────────┐         ┌──────────────┐
    │    MySQL     │         │   Storage    │
    │  cards       │         │ PAN keys     │
    │  wallets     │         │              │
    │  transactions│         │              │
    └──────────────┘         └──────────────┘
```

## مكونات الأرشيتيكشر

| المكون | الدور |
|--------|-------|
| CardNumberGenerator | ينتج رقم بطاقة Luhn-valid مع تجنب التصادم |
| WalletService | يخصم/يضيف رصيد لربط المحفظة بالبطاقة |
| CardIssued Event | يشغل الإشعارات وتسجيل المعاملات |
| Encryption Layer | يشفر PAN قبل التخزين (AES-256) |
