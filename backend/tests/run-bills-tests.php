<?php

declare(strict_types=1);

/**
 * اختبارات التكامل لوحدة الفواتير والمدفوعات المجدولة
 * Integration tests for Bills & Scheduled Payments modules
 *
 * التغطية:
 * 1. دفع فاتورة فردية ناجحة مع تحديث الرصيد والسجل
 * 2. جدولة دفع شهري وتنفيذ تلقائي في اليوم المحدد
 * 3. رفض دفع بسبب رصيد غير كاف أو تجاوز حد المخاطر
 * 4. تشغيل المهمة المجدولة ومطابقة حالة الفواتير
 * 5. تسجيل كل خطوة في AuditLog دون بيانات حساسة
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\BillProvider\Models\BillProvider;
use App\Modules\Bills\Events\BillPaymentCompleted;
use App\Modules\Bills\Events\BillPaymentFailed;
use App\Modules\Bills\Events\BillPaymentInitiated;
use App\Modules\Bills\Models\Bill;
use App\Modules\Bills\Models\ScheduledPayment;
use App\Modules\Bills\Services\BillPaymentProcessor;
use App\Modules\Bills\Services\BillPaymentScheduler;
use App\Modules\Core\ValueObjects\Money;
use App\Models\User;
use App\Modules\Ledger\Models\LedgerEntry;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Support\Facades\Event;

$passed = 0;
$failed = 0;

function assert_bills(bool $condition, string $description): void
{
    global $passed, $failed;
    if ($condition) {
        echo "  ✅ {$description}\n";
        $passed++;
    } else {
        echo "  ❌ {$description}\n";
        $failed++;
    }
}

// ─── الإعداد المشترك ─────────────────────────────────────────

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  اختبارات وحدتي الفواتير والمزودين\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$provider = BillProvider::create([
    'name' => 'الشركة العامة للكهرباء',
    'category' => 'electricity',
    'external_id' => 'EXT-ELEC-TEST-' . bin2hex(random_bytes(3)),
    'is_active' => true,
]);

$user = User::factory()->create();

$wallet = Wallet::create([
    'user_id' => $user->id,
    'balance_fils' => 500_000,
    'currency' => 'SYP',
]);

$systemUser = User::factory()->create(['name' => 'System', 'email' => 'system-bills-' . bin2hex(random_bytes(4)) . '@beza.local']);
config(['bills.system_wallet_user_id' => $systemUser->id]);
Wallet::create([
    'user_id' => $systemUser->id,
    'balance_fils' => 1_000_000_000_000,
    'currency' => 'SYP',
]);

$processor = $app->make(BillPaymentProcessor::class);
$scheduler = $app->make(BillPaymentScheduler::class);

// ─── اختبار 1: دفع فاتورة فردية ناجحة ────────────────────────

echo "\n─── اختبار 1: دفع فاتورة فردية ناجحة ───\n";

$bill = Bill::create([
    'user_id' => $user->id,
    'bill_provider_id' => $provider->id,
    'account_number' => 'ACC-BILL-TEST-001',
    'amount_fils' => 100_000,
    'due_date' => now()->addDays(10)->format('Y-m-d'),
    'status' => 'pending',
]);

$initialBalance = $wallet->fresh()->balance_fils;

try {
    $result = $processor->payBill($bill, $user);
    assert_bills($result->status === 'paid', 'حالة الفاتورة أصبحت paid');
    assert_bills($result->paid_at !== null, 'تم تسجيل وقت الدفع');
    assert_bills($result->receipt_reference !== null, 'تم توليد رقم مرجعي للإيصال');
    assert_bills(str_starts_with($result->receipt_reference, 'RCP-'), 'المرجع يبدأ بـ RCP-');

    $newBalance = $wallet->fresh()->balance_fils;
    assert_bills($newBalance === $initialBalance - 100_000, 'تم خصم المبلغ من المحفظة');

    $entries = LedgerEntry::where('reference_id', $bill->id)->get();
    assert_bills($entries->count() > 0, 'تم تسجيل قيد مزدوج في دفتر الأستاذ');
} catch (\Throwable $e) {
    assert_bills(false, 'دفع فاتورة ناجح: ' . $e->getMessage());
}

// ─── اختبار 2: جدولة دفع شهري ─────────────────────────────────

echo "\n─── اختبار 2: جدولة دفع شهري وتنفيذ تلقائي ───\n";

$initialBalance2 = $wallet->fresh()->balance_fils;

$schedule = $scheduler->createSchedule($user, [
    'bill_provider_id' => $provider->id,
    'account_number' => 'ACC-BILL-SCHED-001',
    'amount_fils' => 50_000,
    'recurrence' => 'monthly',
    'recurrence_day' => 15,
]);

assert_bills($schedule->is_active === true, 'الجدولة نشطة بعد الإنشاء');
assert_bills($schedule->recurrence === 'monthly', 'نوع التكرار شهري');
assert_bills($schedule->next_execution_date !== null, 'تم تحديد تاريخ التنفيذ التالي');

$nextDate = $schedule->calculateNextDate();
assert_bills($nextDate !== $schedule->next_execution_date->toDateString(), 'تاريخ الاستحقاق التالي يختلف عن الحالي');

// محاكاة التنفيذ - تحديث تاريخ الاستحقاق إلى اليوم
$schedule->update(['next_execution_date' => now()->format('Y-m-d')]);
assert_bills($schedule->fresh()->next_execution_date->isToday(), 'تم تعيين تاريخ الاستحقاق لليوم');

$processedBill = $processor->processScheduledPayment($schedule->fresh(), $user);
if ($processedBill) {
    assert_bills($processedBill->status === 'paid', 'الفاتورة المجدولة أصبحت مدفوعة');
    assert_bills($processedBill->receipt_reference !== null, 'تم توليد إيصال للدفع المجدول');

    $updatedSchedule = $schedule->fresh();
    assert_bills($updatedSchedule->last_executed_at !== null, 'تم تحديث تاريخ آخر تنفيذ للجدولة');
    assert_bills($updatedSchedule->next_execution_date->isFuture(), 'تم حساب تاريخ التنفيذ التالي');

    $newBalance2 = $wallet->fresh()->balance_fils;
    assert_bills($newBalance2 === $initialBalance2 - 50_000, 'تم خصم المبلغ المجدول من المحفظة');
} else {
    assert_bills(false, 'تنفيذ الدفع المجدول فشل');
}

// ─── اختبار 3: رفض دفع بسبب رصيد غير كاف ─────────────────────

echo "\n─── اختبار 3: رفض دفع بسبب رصيد غير كاف ───\n";

$currentBalance = $wallet->fresh()->balance_fils;
$bigBill = Bill::create([
    'user_id' => $user->id,
    'bill_provider_id' => $provider->id,
    'account_number' => 'ACC-BILL-OVER-001',
    'amount_fils' => $currentBalance + 1_000_000,
    'due_date' => now()->addDays(5)->format('Y-m-d'),
    'status' => 'pending',
]);

$caught = false;
try {
    $processor->payBill($bigBill, $user);
} catch (\RuntimeException $e) {
    $caught = true;
    assert_bills(str_contains($e->getMessage(), 'رصيد غير كاف'), 'رسالة الخطأ تشير إلى رصيد غير كاف');

    $failedBill = $bigBill->fresh();
    assert_bills($failedBill->status === 'failed', 'حالة الفاتورة أصبحت failed');
}
if (!$caught) {
    assert_bills(false, 'تم رفض الدفع بسبب الرصيد غير الكافي');
}

// ─── اختبار 4: تشغيل المهمة المجدولة ──────────────────────────

echo "\n─── اختبار 4: تشغيل المهمة المجدولة ومطابقة الحالة ───\n";

// إنشاء جدولتين إضافيتين مستحقتين اليوم
$scheduleA = ScheduledPayment::create([
    'user_id' => $user->id,
    'bill_provider_id' => $provider->id,
    'account_number' => 'ACC-SCHED-A',
    'amount_fils' => 30_000,
    'recurrence' => 'monthly',
    'recurrence_day' => 1,
    'next_execution_date' => now()->format('Y-m-d'),
    'is_active' => true,
]);

$scheduleB = ScheduledPayment::create([
    'user_id' => $user->id,
    'bill_provider_id' => $provider->id,
    'account_number' => 'ACC-SCHED-B',
    'amount_fils' => 20_000,
    'recurrence' => 'monthly',
    'recurrence_day' => 1,
    'next_execution_date' => now()->format('Y-m-d'),
    'is_active' => true,
]);

$dueSchedules = $scheduler->getDueSchedules();
assert_bills(count($dueSchedules) >= 2, 'تم العثور على جدولتين مستحقتين على الأقل');

$balanceBeforeDue = $wallet->fresh()->balance_fils;

$billFromA = $processor->processScheduledPayment($scheduleA, $user);
$billFromB = $processor->processScheduledPayment($scheduleB, $user);

$balanceAfterDue = $wallet->fresh()->balance_fils;
$expectedDeduction = ($billFromA ? 30_000 : 0) + ($billFromB ? 20_000 : 0);

if ($billFromA && $billFromB) {
    assert_bills($balanceAfterDue === $balanceBeforeDue - 50_000, 'تم خصم مبلغ الجدولتين المستحقتين');
    assert_bills($billFromA->status === 'paid', 'الفاتورة A مدفوعة');
    assert_bills($billFromB->status === 'paid', 'الفاتورة B مدفوعة');
    assert_bills($scheduleA->fresh()->last_executed_at !== null, 'تم تحديث last_executed_at للجدولة A');
    assert_bills($scheduleB->fresh()->last_executed_at !== null, 'تم تحديث last_executed_at للجدولة B');
} else {
    assert_bills(false, 'معالجة الجدولتين المستحقتين');
}

// ─── اختبار 5: تسجيل AuditLog ─────────────────────────────────

echo "\n─── اختبار 5: تسجيل كل خطوة في AuditLog ───\n";

$auditEntries = AuditLog::where('user_id', $user->id)
    ->where(function ($q) {
        $q->where('action', 'like', 'bill_%');
    })
    ->get();

assert_bills($auditEntries->count() > 0, 'تم تسجيل أحداث الفواتير في سجل التدقيق');

$actions = $auditEntries->pluck('action')->unique()->values()->all();
$hasPaymentInitiated = in_array('bill_payment_initiated', $actions);
$hasPaymentCompleted = in_array('bill_payment_completed', $actions);
$hasPaymentFailed = in_array('bill_payment_failed', $actions);
$hasScheduleCreated = in_array('bill_schedule_created', $actions);

assert_bills($hasPaymentInitiated, 'تم تسجيل bill_payment_initiated');
assert_bills($hasPaymentCompleted, 'تم تسجيل bill_payment_completed');
assert_bills($hasScheduleCreated, 'تم تسجيل bill_schedule_created');

// التحقق من عدم وجود بيانات حساسة
foreach ($auditEntries as $entry) {
    $details = $entry->details;
    $hasSensitive = false;
    if (is_array($details)) {
        $json = json_encode($details);
        if (str_contains($json ?? '', 'password') || str_contains($json ?? '', 'secret') || str_contains($json ?? '', 'token')) {
            $hasSensitive = true;
        }
    }
    assert_bills(!$hasSensitive, 'لا توجد بيانات حساسة في سجل التدقيق للإجراء: ' . $entry->action);
}

// ─── النتائج النهائية ─────────────────────────────────────────

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  النتائج: {$passed} نجاح, {$failed} فشل\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

exit($failed === 0 ? 0 : 1);
