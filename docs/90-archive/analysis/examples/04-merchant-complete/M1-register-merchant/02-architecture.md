# 02 - البنية المعمارية (Architecture) - تسجيل تاجر (Merchant Registration)

```
┌────────────────────────────────────────────────────┐
│ Flutter / React                                     │
│ [RegisterScreen] → [API Request]                    │
└────────────────────┬───────────────────────────────┘
                     │ POST /api/v1/merchant/register
                     ▼
┌────────────────────────────────────────────────────┐
│ Laravel Middleware                                  │
│ auth:api → throttle → verified                 │
└────────────────────┬───────────────────────────────┘
                     ▼
┌────────────────────────────────────────────────────┐
│ MerchantRegisterController                          │
│ 1. Validate                                         │
│ 2. Upload documents                                 │
│ 3. Call RegistrationService                         │
│ 4. Return response                                  │
└────────────────────┬───────────────────────────────┘
                     ▼
┌────────────────────────────────────────────────────┐
│ MerchantRegistrationService                         │
│ 1. Check duplicates                                 │
│ 2. DB::transaction { create merchant + docs }       │
│ 3. Dispatch MerchantRegistered event                │
└────────────────────┬───────────────────────────────┘
                     │
          ┌──────────┴──────────┐
          ▼                     ▼
    ┌──────────┐         ┌──────────┐
    │  MySQL   │         │ Storage  │
    │merchants │         │ Documents│
    │wallets   │         │          │
    └──────────┘         └──────────┘
```
