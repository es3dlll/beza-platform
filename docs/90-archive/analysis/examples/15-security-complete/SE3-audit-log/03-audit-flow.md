# 03 - تدفق تسجيل الأحداث (Audit Flow)

## تدفق معاملة مالية

```
المستخدم            TransferService         ActivityLogger        audit_logs
  │                     │                      │                    │
  │ معاملة جديدة       │                      │                    │
  │────────────────────>│                      │                    │
  │                     │                      │                    │
  │                     │ 1. تحقق من PIN       │                    │
  │                     │ 2. تحقق من الرصيد    │                    │
  │                     │ 3. DB::transaction   │                    │
  │                     │                      │                    │
  │                     │ log('transfer', ...) │                    │
  │                     │─────────────────────>│                    │
  │                     │                      │ INSERT INTO        │
  │                     │                      │ audit_logs (...)    │
  │                     │                      │───────────────────>│
  │                     │                      │                    │
  │                     │ 4. return success    │                    │
  │<────────────────────│                      │                    │
```

## تدفق تسجيل الدخول (Middleware)

```
المستخدم            LoginController         AuditMiddleware       audit_logs
  │                     │                      │                    │
  │ POST /auth/login   │                      │                    │
  │────────────────────>│                      │                    │
  │                     │ تحقق من البيانات     │                    │
  │                     │                      │                    │
  │                     │ login successful     │                    │
  │                     │ log('login', ...)    │                    │
  │                     │─────────────────────>│───────────────────>│
  │                     │                      │                    │
  │  Response + Token  │                      │                    │
  │<────────────────────│                      │                    │
```
