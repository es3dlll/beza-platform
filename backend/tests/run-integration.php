<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Wallet\Models\Wallet;
use App\Models\User;
use App\Modules\Ledger\Services\CoreFinancialEngine;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\Ledger\Models\LedgerEntry;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Notification\Models\NotificationMessage;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    echo "=== Test 1: Transfer creates audit log entry ===\n";

    $sender = User::factory()->create();
    $senderWallet = Wallet::factory()->create([
        'user_id' => $sender->id,
        'balance_fils' => 10000,
    ]);
    $receiver = User::factory()->create();
    $receiverWallet = Wallet::factory()->create([
        'user_id' => $receiver->id,
        'balance_fils' => 0,
    ]);

    $engine = app(CoreFinancialEngine::class);
    $money = Money::fromFils(2000);
    $entry = $engine->transfer(
        amount: $money,
        from: $senderWallet,
        to: $receiverWallet,
        description: 'تحقيق اختبار',
        referenceType: 'transfer',
        referenceId: 'INT-TEST-AUDIT-001',
        fromUserId: $sender->id,
        toUserId: $receiver->id,
    );

    assert($entry !== null, 'Entry should not be null');

    $auditLogs = AuditLog::where('action', 'wallet_transfer')
        ->where('resource_id', $entry->id)
        ->get();

    assert($auditLogs->count() === 1, 'Should create 1 audit log entry. Got: ' . $auditLogs->count());

    $ae = $auditLogs->first();
    assert($ae->user_id === $sender->id, 'User ID should match');
    assert($ae->result === 'success', 'Result should be success');
    assert($ae->metadata['amount_fils'] === 2000, 'Amount should be 2000');
    assert($ae->metadata['to_user_id'] === $receiver->id, 'Receiver ID should match');

    echo "  PASS: Audit log entry created with correct data\n";

    echo "\n=== Test 2: Transfer creates notification for sender ===\n";

    $sender2 = User::factory()->create();
    $senderWallet2 = Wallet::factory()->create([
        'user_id' => $sender2->id,
        'balance_fils' => 10000,
    ]);
    $receiver2 = User::factory()->create();
    $receiverWallet2 = Wallet::factory()->create([
        'user_id' => $receiver2->id,
        'balance_fils' => 0,
    ]);

    $money2 = Money::fromFils(1500);
    $entry2 = $engine->transfer(
        amount: $money2,
        from: $senderWallet2,
        to: $receiverWallet2,
        description: 'اختبار الإشعارات',
        referenceType: 'transfer',
        referenceId: 'INT-TEST-NOTIF-001',
        fromUserId: $sender2->id,
        toUserId: $receiver2->id,
    );

    $notifs = NotificationMessage::where('user_id', $sender2->id)
        ->where('reference_id', $entry2->id)
        ->get();

    assert($notifs->count() >= 1, 'Should create notification for sender. Got: ' . $notifs->count());

    $n = $notifs->first();
    assert($n->type === 'transfer_sent', 'Type should be transfer_sent. Got: ' . $n->type);
    assert(str_contains($n->body, '1.5'), 'Body should contain amount');

    echo "  PASS: Notification created for sender\n";

    echo "\n=== Test 3: Transfer updates balances correctly ===\n";

    $sender3 = User::factory()->create();
    $senderWallet3 = Wallet::factory()->create([
        'user_id' => $sender3->id,
        'balance_fils' => 50000,
    ]);
    $receiver3 = User::factory()->create();
    $receiverWallet3 = Wallet::factory()->create([
        'user_id' => $receiver3->id,
        'balance_fils' => 10000,
    ]);

    $money3 = Money::fromFils(7500);
    $entry3 = $engine->transfer(
        amount: $money3,
        from: $senderWallet3,
        to: $receiverWallet3,
        description: 'اختبار الاتساق',
        referenceType: 'test',
        referenceId: 'CONSISTENCY-001',
        fromUserId: $sender3->id,
        toUserId: $receiver3->id,
    );

    $senderWallet3->refresh();
    $receiverWallet3->refresh();

    assert($senderWallet3->balance_fils === 42500, 'Sender balance should be 42500. Got: ' . $senderWallet3->balance_fils);
    assert($receiverWallet3->balance_fils === 17500, 'Receiver balance should be 17500. Got: ' . $receiverWallet3->balance_fils);

    $le = LedgerEntry::find($entry3->id);
    assert($le !== null, 'Ledger entry should exist');
    assert($le->amount_fils === 7500, 'Amount should be 7500');
    assert($le->debit_wallet_id === $senderWallet3->id, 'Debit wallet should match');
    assert($le->credit_wallet_id === $receiverWallet3->id, 'Credit wallet should match');
    assert($le->metadata['from_balance_before'] === 50000, 'From before should be 50000');
    assert($le->metadata['from_balance_after'] === 42500, 'From after should be 42500');
    assert($le->metadata['to_balance_before'] === 10000, 'To before should be 10000');
    assert($le->metadata['to_balance_after'] === 17500, 'To after should be 17500');

    echo "  PASS: Balances and ledger entry correct\n";

    echo "\n=== All 3 tests PASSED ===\n";

    DB::rollBack();

} catch (\Throwable $e) {
    DB::rollBack();
    echo "\nFAILED: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
