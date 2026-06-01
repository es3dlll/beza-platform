# 03 - تدفق البيانات (Data Flow Sequence) — تسجيل الخروج (Logout)

## سلسلة الاستدعاءات الكاملة

```
  User              Flutter/React          Laravel API         AuthService         MySQL
   │                     │                     │                   │                 │
   │  Click "تسجيل       │                     │                   │                 │
   │  خروج"              │                     │                   │                 │
   │────────────────────>│                     │                   │                 │
   │                     │                     │                   │                 │
   │                     │  POST /auth/logout  │                   │                 │
   │                     │  Authorization:     │                   │                 │
   │                     │  Bearer token       │                   │                 │
   │                     │────────────────────>│                   │                 │
   │                     │                     │                   │                 │
   │                     │                     │  auth:api     │                 │
   │                     │                     │  middleware        │                 │
   │                     │                     │                   │                 │
   │                     │                     │  AuthService      │                 │
   │                     │                     │  ::logout()       │                 │
   │                     │                     │──────────────────>│                 │
   │                     │                     │                   │                 │
   │                     │                     │  currentAccess    │                 │
   │                     │                     │  Token()->delete()│                 │
   │                     │                     │──────────────────>│────────────────>│
   │                     │                     │                   │                 │
   │                     │  Response 200       │                   │                 │
   │                     │  {message: "تم تسجيل│                   │                 │
   │                     │   الخروج"}          │                   │                 │
   │                     │<────────────────────│                   │                 │
   │                     │                     │                   │                 │
   │  Navigate to        │                     │                   │                 │
   │  login screen       │                     │                   │                 │
   │<────────────────────│                     │                   │                 │
```

## Logout من كل الأجهزة

```
  User              Flutter/React          Laravel API         AuthService         MySQL
   │                     │                     │                   │                 │
   │                     │  POST /auth/logout- │                   │                 │
   │                     │  all                │                   │                 │
   │                     │────────────────────>│                   │                 │
   │                     │                     │                   │                 │
   │                     │                     │  user->tokens()   │                 │
   │                     │                     │  ->delete()       │                 │
   │                     │                     │──────────────────>│────────────────>│
   │                     │                     │                   │                 │
   │                     │  Response 200       │                   │                 │
   │                     │  {deleted: 3}       │                   │                 │
   │                     │<────────────────────│                   │                 │
```
