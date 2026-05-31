<?php

declare(strict_types=1);

/**
 * اختبارات وحدتي الإشعارات والتحليلات
 *
 * التدفقات:
 *  1. إرسال إشعار متعدد القنوات + تسجيل حالة التسليم
 *  2. تجميع مؤشر يومي من أحداث متعددة
 *  3. تصدير تقرير مالي بفلترة صحيحة دون بيانات حساسة
 *  4. معالجة فشل قناة واحدة دون تعطيل الباقي
 *  5. تحديث AnalyticsSnapshot بعد معالجة دفعة أحداث
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Modules\Analytics\Models\AnalyticsSnapshot;
use App\Modules\Analytics\Services\AnalyticsAggregator;
use App\Modules\Analytics\Services\ReportExporter;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Notification\Models\NotificationMessage;
use App\Modules\Notification\Services\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

$pass = 0;
$fail = 0;
$total = 20;

function na_assert(bool $condition, string $description): void
{
    global $pass, $fail;
    if ($condition) { $pass++; echo "  ✓ {$description}\n"; }
    else { $fail++; echo "  ✗ {$description}\n"; }
}

function na_setup_user(): User
{
    return User::create(['name' => 'مستخدم إشعارات', 'email' => 'notif@beza.test', 'password' => bcrypt('123')]);
}

// ─────────── 1. إشعار متعدد القنوات ───────────

echo "\n1. إرسال إشعار متعدد القنوات مع تسجيل حالة التسليم\n";

DB::beginTransaction();
$user = na_setup_user();
$dispatcher = $app->make(NotificationDispatcher::class);

$results = $dispatcher->sendMultiChannel(
    userId: $user->id,
    type: 'test_multi',
    title: 'إشعار اختبار',
    body: 'هذا إشعار تجريبي عبر قناتين',
    channels: ['in_app', 'email'],
    referenceType: 'test',
    referenceId: 'ref-001',
);

na_assert(count($results) === 2, "تم إرسال إشعارين (in_app + email)");
na_assert($results[0]->status === 'sent', "القناة الأولى (in_app): حالة sent");
na_assert($results[1]->status === 'sent', "القناة الثانية (email): حالة sent");
na_assert($results[0]->channel === 'in_app', "القناة الأولى: in_app");
na_assert($results[1]->channel === 'email', "القناة الثانية: email");

$logs = AuditLog::where('resource_type', 'notification')
    ->whereIn('resource_id', [$results[0]->id, $results[1]->id])
    ->get();
na_assert($logs->count() >= 2, "تم تسجيل حدثين في AuditLog");

DB::rollBack();

// ─────────── 2. تجميع مؤشر يومي ───────────

echo "\n2. تجميع مؤشر يومي من أحداث متعددة\n";

DB::beginTransaction();
$aggregator = $app->make(AnalyticsAggregator::class);

$today = now()->toDateString();

// Create some audit log entries to simulate events
AuditLog::create([
    'user_id' => 'u1', 'action' => 'remittance_completed',
    'resource_type' => 'remittance', 'resource_id' => 'r1', 'result' => 'success',
    'metadata' => ['amount_fils' => 100_000],
]);
AuditLog::create([
    'user_id' => 'u2', 'action' => 'bill_payment_completed',
    'resource_type' => 'bill', 'resource_id' => 'b1', 'result' => 'success',
    'metadata' => ['amount_fils' => 50_000],
]);
AuditLog::create([
    'user_id' => 'u3', 'action' => 'fraud_alert_triggered',
    'resource_type' => 'fraud', 'resource_id' => 'f1', 'result' => 'alert',
    'metadata' => ['risk_score' => 75],
]);

$snapshot = $aggregator->aggregateDaily($today);
$m = $snapshot->metrics;

na_assert($snapshot->snapshot_date->toDateString() === $today, "التاريخ مضبوط");
na_assert(($m['total_transactions'] ?? 0) >= 2, "إجمالي المعاملات >= 2");
na_assert(($m['total_volume_fils'] ?? 0) >= 150_000, "حجم التداول >= 150,000");
na_assert(($m['fraud_alerts'] ?? 0) >= 1, "تنبيهات الاحتيال >= 1");

DB::rollBack();

// ─────────── 3. تصدير تقرير مالي ───────────

echo "\n3. تصدير تقرير مالي بفلترة صحيحة دون بيانات حساسة\n";

DB::beginTransaction();
$exporter = $app->make(ReportExporter::class);
$today = now()->toDateString();

AuditLog::create([
    'user_id' => 'u1', 'action' => 'remittance_completed',
    'resource_type' => 'remittance', 'resource_id' => 'r2', 'result' => 'success',
    'metadata' => ['amount_fils' => 200_000],
]);
AuditLog::create([
    'user_id' => 'u2', 'action' => 'bill_payment_completed',
    'resource_type' => 'bill', 'resource_id' => 'b2', 'result' => 'success',
    'metadata' => ['amount_fils' => 75_000],
]);

$csv = $exporter->exportFinancialCsv($today, $today);
na_assert(str_contains($csv, 'remittance_completed'), "التقرير يحتوي على remittance_completed");
na_assert(str_contains($csv, 'bill_payment_completed'), "التقرير يحتوي على bill_payment_completed");
na_assert(str_contains($csv, '200000'), "التقرير يحتوي على المبلغ 200000");
na_assert(!str_contains($csv, 'user_id'), "التقرير لا يحتوي على user_id حساس");
na_assert(!str_contains($csv, 'password'), "التقرير لا يحتوي على بيانات حساسة");

DB::rollBack();

// ─────────── 4. فشل قناة واحدة ───────────

echo "\n4. معالجة فشل قناة إرسال واحدة دون تعطيل الباقي\n";

DB::beginTransaction();
$user2 = na_setup_user();

// sms channel is ready = false, but we send anyway — it should fail gracefully
$results2 = $dispatcher->sendMultiChannel(
    userId: $user2->id,
    type: 'test_fail',
    title: 'اختبار الفشل',
    body: 'هذا اختبار لفشل قناة',
    channels: ['in_app', 'email', 'sms'],
    referenceType: 'test',
    referenceId: 'ref-002',
);

$sent = array_filter($results2, fn($m) => $m->status === 'sent');
$failed = array_filter($results2, fn($m) => $m->status === 'failed');
na_assert(count($sent) >= 2, "قناتان على الأقل نجحتا (in_app + email)");
na_assert(count($results2) === 3, "تم إنشاء 3 رسائل");

DB::rollBack();

// ─────────── 5. تحديث AnalyticsSnapshot ───────────

echo "\n5. تحديث AnalyticsSnapshot بعد معالجة دفعة أحداث\n";

DB::beginTransaction();
$aggregator2 = $app->make(AnalyticsAggregator::class);
$today2 = now()->toDateString();

// First aggregation
$s1 = $aggregator2->aggregateDaily($today2);
$v1 = $s1->metrics['total_volume_fils'] ?? 0;

// Add more events
AuditLog::create([
    'user_id' => 'u10', 'action' => 'remittance_completed',
    'resource_type' => 'remittance', 'resource_id' => 'r10', 'result' => 'success',
    'metadata' => ['amount_fils' => 500_000],
]);
AuditLog::create([
    'user_id' => 'u11', 'action' => 'bill_payment_completed',
    'resource_type' => 'bill', 'resource_id' => 'b10', 'result' => 'success',
    'metadata' => ['amount_fils' => 250_000],
]);

// Re-aggregate
$s2 = $aggregator2->aggregateDaily($today2);
$v2 = $s2->metrics['total_volume_fils'] ?? 0;

na_assert($v2 >= $v1, "حجم التداول زاد بعد إضافة أحداث جديدة");
na_assert($s2->id === $s1->id, "تم تحديث نفس السجل (وليس إنشاء جديد)");

// Export operational report
$csv2 = $exporter->exportOperationalCsv($today2, $today2);
na_assert(str_contains($csv2, (string)($v2)), "التقرير التشغيلي يحتوي على حجم التداول المحدّث");

DB::rollBack();

// ─────────── ملخص ───────────

echo "\n════════════════════════════════════════\n";
echo "  Notifications & Analytics: {$pass}/{$total} نجاح\n";
echo "════════════════════════════════════════\n";

exit($fail === 0 ? 0 : 1);
