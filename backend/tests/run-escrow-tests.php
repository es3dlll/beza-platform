<?php

declare(strict_types=1);

/**
 * اختبارات وحدة الضمان المالي (Escrow)
 *
 * التدفقات المغطاة:
 *  1. إنشاء معاملة ضمان + حجز + إطلاق + إرجاع
 *  2. فتح نزاع + قرار لصالح المشتري (إرجاع)
 *  3. قرار لصالح التاجر (إطلاق)
 *  4. قرار تقسيم 50/50
 *  5. AuditLog لكل حدث
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\Escrow\Exceptions\EscrowException;
use App\Modules\Escrow\Models\DisputeCase;
use App\Modules\Escrow\Models\EscrowTransaction;
use App\Modules\Escrow\Services\EscrowCustodianService;
use App\Modules\Marketplace\Models\Seller;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Support\Facades\DB;

$pass = 0;
$fail = 0;
$total = 32;

function escrow_assert(bool $condition, string $description): void
{
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "  ✓ {$description}\n";
    } else {
        $fail++;
        echo "  ✗ {$description}\n";
    }
}

function escrow_setup(): array
{
    DB::beginTransaction();

    $escrowUser = User::create(['name' => 'نظام الضمان', 'email' => 'escrow-sys@beza.test', 'password' => bcrypt('123')]);
    config(['escrow.system_wallet_user_id' => $escrowUser->id]);

    $buyer = User::create(['name' => 'مشتري', 'email' => 'buyer@escrow.test', 'password' => bcrypt('123')]);
    $seller = User::create(['name' => 'بائع', 'email' => 'seller@escrow.test', 'password' => bcrypt('123')]);

    $sellerRecord = Seller::create([
        'user_id' => $seller->id,
        'business_name' => 'متجر اختبار',
        'status' => 'approved',
    ]);

    Wallet::create(['user_id' => $buyer->id, 'balance_fils' => 100_000_000, 'currency' => 'SYP']);
    Wallet::create(['user_id' => $seller->id, 'balance_fils' => 0, 'currency' => 'SYP']);
    Wallet::create(['user_id' => $escrowUser->id, 'balance_fils' => 0, 'currency' => 'SYP']);

    return [$buyer, $sellerRecord, $sellerRecord->id, $seller->id];
}

// ─────────────────────────── حزمة 1: تدفق كامل ───────────────────────────

echo "\n1. تدفق الضمان الكامل (initiate → fund → release)\n";

[$buyer, $sellerRecord, $sellerId, $sellerUserId] = escrow_setup();

$escrow = $app->make(EscrowCustodianService::class);

$tx = $escrow->initiate($buyer, $sellerId, 50_000_000, 'ORD-001');
escrow_assert($tx->status === 'initiated', "initiate: حالة initiated");
escrow_assert($tx->amount_fils === 50_000_000, "initiate: مبلغ 50,000,000 فلس");
escrow_assert($tx->fee_fils === 500_000, "initiate: رسم 500,000 فلس (1%)");
escrow_assert($tx->marketplace_ref_id === 'ORD-001', "initiate: reference معرف السوق");

$funded = $escrow->fund($tx);
escrow_assert($funded->status === 'funded', "fund: حالة funded");
escrow_assert($funded->metadata['funded_at'] !== null, "fund: metadata.funded_at");

$buyerWallet = Wallet::where('user_id', $buyer->id)->first();
escrow_assert($buyerWallet->balance_fils === 100_000_000 - 50_500_000, "fund: خصم من المشتري (المبلغ + الرسم)");

$released = $escrow->release($funded);
escrow_assert($released->status === 'released', "release: حالة released");

$sellerWallet = Wallet::where('user_id', $sellerUserId)->first();
escrow_assert($sellerWallet->balance_fils === 50_000_000, "release: إيداع 50,000,000 لدى البائع");

DB::rollBack();

// ─────────────────────────── حزمة 2: نزاع + إرجاع ───────────────────────────

echo "\n2. نزاع وإرجاع للمشتري\n";

[$buyer, $sellerRecord, $sellerId] = escrow_setup();

$escrow = $app->make(EscrowCustodianService::class);

$tx = $escrow->initiate($buyer, $sellerId, 30_000_000);
$funded = $escrow->fund($tx);

$dispute = $escrow->openDispute(
    transaction: EscrowTransaction::find($funded->id),
    raisedBy: $buyer->id,
    reason: 'منتج غير مطابق للوصف',
    description: 'المنتج مختلف تماماً عن الصور',
    documents: [['url' => 'https://example.com/img1.jpg', 'type' => 'image']],
);

escrow_assert($dispute->status === 'open', "dispute: حالة open");
escrow_assert($dispute->reason === 'منتج غير مطابق للوصف', "dispute: سبب النزاع");
escrow_assert(count($dispute->documents) === 1, "dispute: 1 وثيقة");
escrow_assert($dispute->transaction->status === 'disputed', "dispute: المعاملة بحالة disputed");

$result = $escrow->resolveDispute($dispute, 'buyer', 'إرجاع كامل للمشتري', 'admin-001');
escrow_assert($result['dispute']->status === 'resolved', "resolve buyer: dispute resolved");
escrow_assert($result['dispute']->decision === 'buyer', "resolve buyer: decision = buyer");
escrow_assert($result['transaction']->status === 'refunded', "resolve buyer: transaction refunded");

$buyerWallet = Wallet::where('user_id', $buyer->id)->first();
escrow_assert($buyerWallet->balance_fils === 100_000_000, "resolve buyer: إرجاع كامل المبلغ + الرسم");

DB::rollBack();

// ─────────────────────────── حزمة 3: نزاع + تسليم للتاجر ───────────────────────────

echo "\n3. نزاع وقرار لصالح التاجر\n";

[$buyer, $sellerRecord, $sellerId, $sellerUserId] = escrow_setup();

$escrow = $app->make(EscrowCustodianService::class);

$tx = $escrow->initiate($buyer, $sellerId, 20_000_000);
$funded = $escrow->fund($tx);

$dispute = $escrow->openDispute(
    transaction: EscrowTransaction::find($funded->id),
    raisedBy: $buyer->id,
    reason: 'تأخير في الشحن',
    description: 'وصل متأخراً 3 أيام',
);

$result = $escrow->resolveDispute($dispute, 'seller', 'تم الشحن في الوقت المحدد', 'admin-002');
escrow_assert($result['dispute']->status === 'resolved', "resolve seller: dispute resolved");
escrow_assert($result['dispute']->decision === 'seller', "resolve seller: decision = seller");
escrow_assert($result['transaction']->status === 'released', "resolve seller: transaction released");

$sellerWallet = Wallet::where('user_id', $sellerUserId)->first();
escrow_assert($sellerWallet->balance_fils === 20_000_000, "resolve seller: إيداع 20,000,000 للتاجر");

DB::rollBack();

// ─────────────────────────── حزمة 4: تقسيم 50/50 ───────────────────────────

echo "\n4. نزاع وتقسيم 50/50\n";

[$buyer, $sellerRecord, $sellerId, $sellerUserId] = escrow_setup();

$escrow = $app->make(EscrowCustodianService::class);

$tx = $escrow->initiate($buyer, $sellerId, 10_000_000);
$funded = $escrow->fund($tx);

$dispute = $escrow->openDispute(
    transaction: EscrowTransaction::find($funded->id),
    raisedBy: $buyer->id,
    reason: 'عيب في المنتج',
    description: 'المنتج به عيب بسيط',
);

$result = $escrow->resolveDispute($dispute, 'split', 'مسؤولية مشتركة', 'admin-003');
escrow_assert($result['dispute']->status === 'resolved', "resolve split: dispute resolved");
escrow_assert($result['dispute']->decision === 'split', "resolve split: decision = split");
escrow_assert($result['transaction']->status === 'split', "resolve split: transaction split");

$buyerWallet = Wallet::where('user_id', $buyer->id)->first();
$sellerWallet = Wallet::where('user_id', $sellerUserId)->first();
escrow_assert($buyerWallet->balance_fils === 94_900_000, "split: المشتري يسترد 5,000,000 (94,900,000)");
escrow_assert($sellerWallet->balance_fils === 5_000_000, "split: البائع يحصل على 5,000,000");

DB::rollBack();

// ─────────────────────────── حزمة 5: AuditLog ───────────────────────────

echo "\n5. سجل التدقيق (AuditLog)\n";

[$buyer, $sellerRecord, $sellerId, $sellerUserId] = escrow_setup();

$escrow = $app->make(EscrowCustodianService::class);

$tx = $escrow->initiate($buyer, $sellerId, 15_000_000);
$funded = $escrow->fund($tx);

$logsAfterFund = AuditLog::where('resource_type', 'escrow')
    ->where('resource_id', $funded->id)
    ->orderBy('created_at')
    ->get();

$logCount = $logsAfterFund->count();
$actions = $logsAfterFund->pluck('action')->implode(', ');
escrow_assert($logCount >= 2, "audit: {$logCount} أحداث بعد initiate+fund ({$actions})");
if ($logCount >= 2) {
    escrow_assert($logsAfterFund[0]->action === 'escrow_initiated', "audit: أول حدث هو escrow_initiated");
    escrow_assert($logsAfterFund[1]->action === 'escrow_funded', "audit: ثاني حدث هو escrow_funded");
    escrow_assert(($logsAfterFund[1]->metadata['amount_fils'] ?? 0) === 15_000_000, "audit: metadata يحتوي amount_fils");
}

$released = $escrow->release($funded);

$logsAfterRelease = AuditLog::where('resource_type', 'escrow')
    ->where('resource_id', $released->id)
    ->orderBy('created_at')
    ->get();

escrow_assert($logsAfterRelease->count() >= 3, "audit: {$logsAfterRelease->count()} أحداث بعد الإطلاق");

// Dispute a separate funded transaction
$tx2 = $escrow->initiate($buyer, $sellerId, 8_000_000);
$funded2 = $escrow->fund($tx2);

$dispute2 = $escrow->openDispute(
    transaction: EscrowTransaction::find($funded2->id),
    raisedBy: $buyer->id,
    reason: 'اختبار Audit',
    description: 'وصف اختبار',
);
$result2 = $escrow->resolveDispute($dispute2, 'buyer', 'إرجاع', 'admin');

$logsFinal = AuditLog::where('resource_type', 'escrow')
    ->whereIn('resource_id', [$funded2->id, $result2['transaction']->id])
    ->orderBy('created_at')
    ->get();

$disputedCount = $logsFinal->where('action', 'escrow_disputed')->count();
escrow_assert($disputedCount >= 1, "audit: {$disputedCount} أحداث escrow_disputed مسجلة");

DB::rollBack();

// ─────────────────────────── ملخص ───────────────────────────

echo "\n════════════════════════════════════════\n";
echo "  Escrow: {$pass}/{$total} نجاح\n";
echo "════════════════════════════════════════\n";

exit($fail === 0 ? 0 : 1);
