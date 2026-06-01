# 15 - تحسين الاستعلامات (Query Optimization)

## مبادئ تحسين الاستعلامات

1. **استخدم الفهارس المناسبة** — INDEX على الأعمدة المستخدمة في WHERE
2. **تجنب SELECT \*** — اختر الأعمدة التي تحتاجها فقط
3. **استخدم LIMIT** — لا تجلب أكثر مما تحتاج
4. **تجنب N+1** — استخدم eager loading (with())
5. **استخدم EXPLAIN** — تحليل خطة التنفيذ

## قبل التحسين وبعده

```php
// ❌ بطيء: N+1 problem
$transactions = Transaction::all();
foreach ($transactions as $txn) {
    echo $txn->fromWallet->user->name; // استعلام إضافي لكل معاملة
}

// ✅ سريع: Eager Loading
$transactions = Transaction::with('fromWallet.user')->get();
foreach ($transactions as $txn) {
    echo $txn->fromWallet->user->name; // استعلام واحد فقط
}
```

```php
// ❌ بطيء: جلب كل الأعمدة
$users = User::all();
foreach ($users as $user) {
    echo $user->name;
}

// ✅ سريع: جلب الأعمدة المطلوبة فقط
$users = User::select('id', 'name', 'phone')->get();
```

## تحسين استعلامات التقارير

```sql
-- ❌ بطيء: COUNT مع GROUP BY على جدول كبير
SELECT user_id, COUNT(*) FROM transactions GROUP BY user_id;

-- ✅ سريع: استخدام فهرس مركب + تقييد التاريخ
SELECT from_wallet_id, COUNT(*), SUM(amount)
FROM transactions
WHERE created_at >= '2026-01-01'
  AND type = 'transfer'
  AND status = 'completed'
GROUP BY from_wallet_id;
```

## تحسين استعلام البحث

```php
// ❌ بطيء: LIKE مع % في البداية (لا يستخدم الفهرس)
User::where('phone', 'LIKE', '%1234%')->get();

// ✅ سريع: استخدام فهرس B-Tree
User::where('phone', 'LIKE', '9639%')->get();
```

## استخدام Chunk للبيانات الكبيرة

```php
// معالجة 1 مليون معاملة بدون تجاوز الذاكرة
Transaction::where('status', 'pending')
    ->chunk(100, function ($transactions) {
        foreach ($transactions as $txn) {
            // معالجة كل معاملة
        }
    });
```
