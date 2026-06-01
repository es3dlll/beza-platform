# 14 - ACID في التقارير المالية

التقارير عمليات **قراءة فقط** بشكل أساسي، ولا تحتاج ACID transactions كاملة لأنها لا تعدل البيانات. لكن فهم ACID ضروري لضمان دقة التقارير.

## مشكلة البيانات غير المتسقة (Data Drift)

عند توليد التقرير اليومي، قد تكون هناك معاملات قيد التنفيذ تؤدي إلى انزلاق البيانات:

```
T1: معاملة تحويل → BEGIN, خصم من المحفظة أ, إضافة إلى المحفظة ب, COMMIT
T2: التقرير → يقرأ SUM(amount) من جدول المعاملات
     ← قد يقرأ قبل COMMIT أو بعده → عدم تناسق
```

**الحلول**:
1. توليد التقرير بعد منتصف الليل (عندما تكون المعاملات قليلة)
2. استخدام `created_at <= '2026-05-27 23:59:59'` بدلاً من `DATE(created_at) = '2026-05-27'`
3. تخزين التقرير في جدول منفصل (قراءة من cache بدلاً من DB مباشرة)

## Atomic Report Generation (إنشاء تقارير ذرية)

لضمان أن التقرير يلتقط لقطة ثابتة (snapshot) من البيانات في لحظة زمنية محددة:

```php
// إنشاء تقرير ذري — يضمن عدم تغير البيانات أثناء القراءة
DB::transaction(function () {
    // 1. تسجيل طابع زمني للتقرير
    $report = DailyReport::create([
        'generated_at'  => now(),
        'snapshot_from' => $startOfDay,
        'snapshot_to'   => $endOfDay,
    ]);

    // 2. قراءة المعاملات ضمن النطاق الزمني المحدد
    $transactions = Transaction::whereBetween('created_at', [
        $startOfDay, $endOfDay
    ])->get();

    // 3. حساب المجاميع
    $report->update([
        'total_deposits'  => $transactions->where('type', 'deposit')->sum('amount'),
        'total_withdraws' => $transactions->where('type', 'withdraw')->sum('amount'),
        'total_transfers' => $transactions->where('type', 'transfer')->sum('amount'),
    ]);
});
```

## Locking Considerations (اعتبارات القفل في التقارير)

تقارير القراءة فقط **لا تحتاج** أقفال قاعدة بيانات لأنها لا تعدل البيانات. لكن في حال التحديث المتزامن للتقارير المخزنة:

```php
// تقرير قراءة فقط — لا حاجة للأقفال
$reportData = DB::select('SELECT SUM(amount) FROM transactions WHERE date = ?', [$date]);

// تحديث تقرير مخزن مع قفل متفائل (optimistic locking) لتجنب الكتابة المتزامنة
$report = DailyReport::where('id', $reportId)->where('version', $currentVersion)->first();
if ($report) {
    $report->update(['total' => $newTotal, 'version' => $currentVersion + 1]);
} else {
    // شخص آخر قام بالتحديث قبلك — أعد المحاولة
}
```

## استخدام Materialized Views للتقارير اليومية

بدلاً من تشغيل استعلامات ثقيلة يومياً، يمكن استخدام materialized views تُحدَّث في أوقات محددة:

```php
// تشغيل تحديث Materialized View في منتصف الليل (cron job)
Artisan::command('reports:refresh-materialized-views', function () {
    // MariaDB/MySQL لا يدعم materialized views أصلاً، لذا نستخدم جدول تخزين مؤقت
    DB::statement('
        REPLACE INTO daily_transactions_summary (date, type, total_amount, count)
        SELECT DATE(created_at) AS date, type, SUM(amount), COUNT(*)
        FROM transactions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
        GROUP BY DATE(created_at), type
    ');
})->dailyAt('00:05');
```

## Consistency بين المعاملات وأرصدة المحافظ

أحد أكبر التحديات: التأكد من أن التقرير يظهر رصيد المحفظة مطابقاً للمعاملات المسجلة:

```php
// التحقق من التناسق: مجموع المعاملات = الفرق في الرصيد
public function assertConsistency($walletId, $startDate, $endDate)
{
    $wallet = Wallet::find($walletId);
    $transactionsSum = Transaction::where('wallet_id', $walletId)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->where('status', 'completed')
        ->sum(DB::raw("CASE WHEN type IN ('deposit','transfer_in') THEN amount ELSE -amount END"));

    $expectedBalance = $wallet->opening_balance + $transactionsSum;

    if (abs($wallet->balance - $expectedBalance) > 0.01) {
        Log::warning("عدم تناسق في الرصيد للمحفظة {$walletId}");
    }
}
```

## معالجة فروق التوقيت (Timezone Handling)

عند تجميع التقارير اليومية، يجب التعامل مع المناطق الزمنية للمستخدمين:

```php
// تخزين كل الطوابع الزمنية بصيغة UTC فقط
// والتحويل عند عرض التقرير حسب منطقة المستخدم

$userTimezone = $request->user()->timezone ?? 'Asia/Damascus';

$report = Transaction::select(
    DB::raw("DATE(CONVERT_TZ(created_at, 'UTC', '{$userTimezone}')) AS local_date"),
    DB::raw('SUM(amount) AS total')
)
->whereBetween('created_at', [$utcStart, $utcEnd])
->groupBy('local_date')
->get();
```

| الأسلوب | الوصف | متى يُستخدم |
|---------|-------|-------------|
| Snapshot ذري | قراءة كل البيانات في transaction واحد | التقارير اللحظية (Real-time) |
| Materialized View | جدول مُجمَّع مسبقاً يُحدَّث دورياً | التقارير اليومية/الشهرية |
| Read Uncommitted | قراءة بدون أقفال (قد يقرأ بيانات غير ملتزمة) | أبداً — يؤدي إلى عدم دقة |
| نسخ احتياطي للقراءة (Replica) | قراءة من قاعدة منفصلة (read replica) | عند وجود ضغط عالٍ على DB |
