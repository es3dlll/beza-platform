# 19 - حالات الحافة (Edge Cases)

## 1. لوحة التحكم فارغة (لا يوجد مستخدمون)

**المشكلة**: المنصة جديدة ولا توجد بيانات.

**الحل**: عرض 0 بدلاً من null أو خطأ.

```php
return [
    'total_users' => User::count() ?: 0,
    'total_transactions' => Transaction::count() ?: 0,
];
```

## 2. Cache منتهي أو Redis معطل

**المشكلة**: Redis غير متاح أو الـ Cache انتهى.

**الحل**: قراءة من DB مباشرة مع تسجيل خطأ.

```php
try {
    $data = Cache::get('dashboard_stats');
} catch (\Exception $e) {
    Log::error('Cache unavailable, reading from DB');
    $data = $this->generateStats($period);
}
```

## 3. فترة زمنية بدون بيانات

**المشكلة**: اختيار فترة زمنية ليس فيها معاملات.

**الحل**: إرجاع مصفوفة فارغة بدلاً من خطأ.

```php
$chart = $query->get()->toArray() ?: [];
```

## 4. مستخدم لديه صلاحيات محدودة

**المشكلة**: مشرف جزئي الصلاحيات (مشرف تقارير فقط).

**الحل**: التحقق من الأدوار والصلاحيات (مستقبلاً).

## 5. أرقام كبيرة جداً

**المشكلة**: إجمالي المحافظ يتجاوز 999,999,999,999,999.99.

**الحل**: استخدام BIGINT أو DECIMAL(20,2) للمبالغ الكبيرة.

## 6. تزامن تحديث Cache

**المشكلة**: طلبان متزامنان عند انتهاء Cache — كلاهما يولد البيانات.

**الحل**: استخدام Cache lock (Redis Lock).

```php
$lock = Cache::lock('dashboard_stats_lock', 10);

if ($lock->get()) {
    $stats = $this->generateStats($period);
    $this->storeInCache($stats);
    $lock->release();
} else {
    $stats = $this->generateStats($period); // ينتظر أو يولد مباشرة
}
```

## 7. فرق التوقيت

**المشكلة**: `today()` قد يختلف حسب توقيت السيرفر.

**الحل**: ضبط `app.timezone` إلى `Asia/Damascus` في config/app.php.

## جدول ملخص

| # | الحالة | النتيجة | الحل |
|---|--------|---------|------|
| 1 | لا بيانات | 200 مع أصفار | 0 بدلاً من null |
| 2 | Redis معطل | قراءة من DB مباشرة | Fallback |
| 3 | فترة فارغة | مصفوفة [] بدلاً من خطأ | array ?: [] |
| 4 | صلاحيات جزئية | 403 | RBAC |
| 5 | أرقام كبيرة | DECIMAL موسع | BIGINT |
| 6 | Race condition على Cache | Lock | Cache Lock |
| 7 | توقيت خاطئ | Asia/Damascus | Config |
