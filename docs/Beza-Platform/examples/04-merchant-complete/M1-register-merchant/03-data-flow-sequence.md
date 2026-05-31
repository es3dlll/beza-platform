# 03 - تدفق البيانات (Data Flow Sequence) - تسجيل تاجر (Merchant Registration)

```
  Merchant      Flutter/React      Laravel API        RegService       MySQL         Admin
     │                │                  │                │               │             │
     │  تسجيل         │                  │                │               │             │
     │--------------->│                  │                │               │             │
     │                │  POST /register  │                │               │             │
     │                │----------------->│                │               │             │
     │                │                  │  Validate      │               │             │
     │                │                  │--------------->│               │             │
     │                │                  │  Check dupl.   │-------------->│             │
     │                │                  │  Create merch  │-------------->│             │
     │                │                  │  Upload docs   │-------------->│             │
     │                │                  │  Dispatch event│               │             │
     │                │ Response 201     │                │               │             │
     │                │<-----------------│                │               │             │
     │                │                  │                │               │  Admin      │
     │                │                  │                │               │  reviews    │
     │                │                  │ PATCH /approve │               │             │
     │                │                  │<-------------------------------│             │
     │                │                  │ Update active  │-------------->│             │
     │                │                  │ Create wallets │-------------->│             │
     │                │                  │ Notify merchant│               │             │
     │<---------------│                  │                │               │             │
```

## شرح التدفق
1. يقدم التاجر طلب التسجيل عبر الواجهة الأمامية (Flutter/React)
2. يتم إرسال البيانات إلى Laravel API عبر POST /register
3. تمر الطلبات عبر Middleware (auth:api, throttle)
4. يتحقق MerchantRegistrationService من التكرارات وينشئ التاجر
