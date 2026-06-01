# الهجرات المطلوبة — WAP

**لا توجد هجرات جديدة.** WAP يعيد استخدام الجداول الحالية:

- `users` ← موجودة (من A1-register)
- `wallets` ← موجودة (من W1)
- `transactions` ← موجودة (من T1)

> إذا كان `idempotency_key` غير موجود في جدول `transactions`، تضاف هجرة:
> `php artisan make:migration add_idempotency_key_to_transactions_table`
