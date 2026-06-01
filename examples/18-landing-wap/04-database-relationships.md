# علاقات قاعدة البيانات — WAP

WAP لا يتطلب جداول جديدة. يعيد استخدام الهيكل الحالي:

```
users
├── id (bigint)
├── name, email, phone, password
├── role: enum('user','merchant','agent','admin')
└── ...

wallets
├── id (bigint)
├── user_id (FK → users)
├── currency: enum('SYP','USD')
├── balance (bigint — بالفلس)
├── blocked_balance (bigint)
└── ...

transactions
├── id (bigint)
├── sender_wallet_id (FK → wallets)
├── receiver_wallet_id (FK → wallets)
├── amount (bigint)
├── type: enum('transfer','deposit','withdrawal','payment')
├── status: enum('pending','held','completed','failed','reversed')
├── idempotency_key (string — unique)
└── ...
```

## ملاحظات WAP-specific
- `idempotency_key` موجود في جدول `transactions` — يستخدم لمنع التكرار
- حقل `role` في `users` يحدد أي لوحة WAP تظهر للمستخدم
- لا حاجة لهجرات إضافية — WAP يعيد استخدام الهيكل الحالي
