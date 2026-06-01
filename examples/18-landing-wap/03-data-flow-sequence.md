# تدفق البيانات — WAP

## 1. تدفق تسجيل الدخول
```
المستخدم               Next.js Server               Laravel API
   │                       │                            │
   │── POST /wap/login ────→│                            │
   │  {email, password}     │                            │
   │                        │── POST /api/v1/wap/auth/login ──→│
   │                        │  {email, password, device:"wap"}│
   │                        │←── {user, token, refresh} ──────│
   │                        │                            │
   │                        │── Set-Cookie: token=JWT     │
   │                        │   (HttpOnly, Secure,        │
   │                        │    SameSite=Strict)         │
   │                        │── Set-Cookie: refresh=...   │
   │                        │                            │
   │←── 302 → /wap/dashboard │                            │
```

## 2. تدفق عرض الرصيد
```
المستخدم               Next.js Server               Laravel API
   │                       │                            │
   │── GET /wap/user ──────→│                            │
   │                        │── GET /api/v1/wap/wallet/balance │
   │                        │   Cookie: token=JWT        │
   │                        │   ?format=minimal          │
   │                        │←── {balance, currency} ───→│
   │←── صفحة الرصيد ────────│                            │
```

## 3. تدفق تحويل مع Offline Queue
```
المستخدم               المتصفح (JS)        IndexedDB        SW        API
   │                       │                  │            │         │
   │── إرسال تحويل ────────→│                  │            │         │
   │                       │── هل الإنترنت؟    │            │         │
   │                       │   ├─ نعم ─────────┼────────────┼─────────→│
   │                       │   │               │            │  POST    │
   │                       │   │               │            │ /transfer│
   │                       │   │               │            │←── 200 ─│
   │                       │   │               │            │         │
   │                       │   └─ لا ─────────→│            │         │
   │                       │     {status:      │            │         │
   │                       │      pending}     │            │         │
   │                       │                  │            │         │
   │                       │  [عودة الاتصال]   │            │         │
   │                       │←── sync ─────────│            │         │
   │                       │  اقرأ pending     │            │         │
   │                       │── أرسل ───────────┼────────────┼─────────→│
   │                       │  idempotency_key  │            │  POST    │
   │                       │                  │            │ /transfer│
   │                       │  ←── 200 ────────┼────────────┼─────────│
   │                       │  status:         │            │         │
   │                       │  completed       │            │         │
```
