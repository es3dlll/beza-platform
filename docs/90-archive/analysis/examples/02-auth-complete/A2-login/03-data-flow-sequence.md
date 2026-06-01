# 03 - تدفق البيانات (Data Flow Sequence) — تسجيل الدخول (Login)

## سلسلة الاستدعاءات الكاملة

```
  User              Flutter/React          Laravel API         AuthService        MySQL            Redis
   │                     │                     │                   │                 │               │
   │  Enter phone +      │                     │                   │                 │               │
   │  password           │                     │                   │                 │               │
   │────────────────────>│                     │                   │                 │               │
   │                     │                     │                   │                 │               │
   │                     │  POST /auth/login   │                   │                 │               │
   │                     │  {phone, password,  │                   │                 │               │
   │                     │   device_id}        │                   │                 │               │
   │                     │────────────────────>│                   │                 │               │
   │                     │                     │                   │                 │               │
   │                     │                     │  throttle:10,1   │                 │               │
   │                     │                     │                   │                 │               │
   │                     │                     │  validate(Login) │                 │               │
   │                     │                     │                   │                 │               │
   │                     │                     │  AuthService      │                 │               │
   │                     │                     │  ::login()        │                 │               │
   │                     │                     │──────────────────>│                 │               │
   │                     │                     │                   │                 │               │
   │                     │                     │  Find user by     │                 │               │
   │                     │                     │  phone            │                 │               │
   │                     │                     │──────────────────>│────────────────>               │
   │                     │                     │                   │                 │               │
   │                     │                     │  Check login      │                 │               │
   │                     │                     │  attempts count   │                 │               │
   │                     │                     │──────────────────>│────────────────────────────────>│
   │                     │                     │                   │                 │               │
   │                     │                     │  Hash::check      │                 │               │
   │                     │                     │  (password)       │                 │               │
   │                     │                     │                   │                 │               │
   │                     │                     │  Check status     │                 │               │
   │                     │                     │  (not suspended)  │                 │               │
   │                     │                     │                   │                 │               │
   │                     │                     │  Update           │                 │               │
   │                     │                     │  last_login_at,   │                 │               │
   │                     │                     │  last_login_ip,   │                 │               │
   │                     │                     │  device_id        │                 │               │
   │                     │                     │──────────────────>│────────────────>               │
   │                     │                     │                   │                 │               │
   │                     │                     │  Delete old       │                 │               │
   │                     │                     │  tokens           │                 │               │
   │                     │                     │──────────────────>│────────────────>               │
   │                     │                     │                   │                 │               │
   │                     │                     │  Create new       │                 │               │
   │                     │                     │  JWT Token    │                 │               │
   │                     │                     │──────────────────>│────────────────>               │
   │                     │                     │                   │                 │               │
   │                     │  Response 200       │                   │                 │               │
   │                     │  {user, token}      │                   │                 │               │
   │                     │<────────────────────│                   │                 │               │
   │                     │                     │                   │                 │               │
   │  Navigate to        │                     │                   │                 │               │
   │  home screen        │                     │                   │                 │               │
   │<────────────────────│                     │                   │                 │               │
```

## جدول زمني لتنفيذ العملية

| الخطوة | الزمن المستغرق | ملاحظة |
|--------|---------------|--------|
| Network Request | ~30ms | |
| Middleware | ~5ms | throttle + guest |
| Validation | ~5ms | |
| Find user | ~10ms | Query MySQL مع Index |
| Hash::check | ~5ms | |
| Update user | ~10ms | |
| Token operations | ~15ms | Delete old + create new |
| Response | ~10ms | |
| **المجموع** | **~90ms** | |
