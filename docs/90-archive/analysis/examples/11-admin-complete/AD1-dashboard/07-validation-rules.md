# 07 - قواعد التحقق للوحة التحكم (Validation Rules)

لوحة التحكم عملية قراءة (GET) فقط — لا تحتاج validation على الـ Request. لكن التحقق يكون على:

## 1. صلاحية المشرف (Authorization)

```php
// AdminMiddleware — التحقق من أن المستخدم مشرف
public function handle(Request $request, Closure $next): mixed
{
    if (!$request->user() || !$request->user()->is_admin) {
        abort(403, 'غير مصرح بالدخول — صلاحيات المشرف مطلوبة');
    }

    return $next($request);
}
```

## 2. معاملات الـ Query (اختيارية)

```php
// التحقق من معاملات التاريخ للرسوم البيانية
'period' => ['sometimes', 'string', Rule::in(['7d', '30d', '90d', '1y'])],
'currency' => ['sometimes', 'string', Rule::in(['SYP', 'USD', 'all'])],
```

## 3. التحقق من صحة Cache

```php
// في DashboardStatsService
$cached = Cache::get('dashboard_stats');

if ($cached && !$this->isExpired($cached)) {
    return $cached['data'];
}

// التحقق من أن البيانات كاملة
$required = ['total_users', 'active_users', 'total_transactions', 
             'transaction_volume', 'charts', 'top_merchants'];

foreach ($required as $field) {
    if (!array_key_exists($field, $data)) {
        throw new IncompleteStatsException($field);
    }
}
```

## جدول التحقق

| المستوى | ما يتم التحقق منه | النتيجة |
|---------|-------------------|---------|
| Middleware | is_admin = true | 403 Forbidden |
| Route | period (اختياري) | 422 if invalid |
| Cache | وجود جميع المفاتيح | توليد جديد |
| DB | وجود بيانات | أصفار بدلاً من null |
