<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Ledger\Models\LedgerEntry;
use App\Modules\Notification\Models\NotificationMessage;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TransferIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_creates_audit_log_entry(): void
    {
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

        $engine = app(\App\Modules\Ledger\Services\CoreFinancialEngine::class);
        $money = \App\Modules\Core\ValueObjects\Money::fromFils(2000);
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

        $this->assertNotNull($entry);

        $auditLogs = AuditLog::where('action', 'wallet_transfer')
            ->where('resource_id', $entry->id)
            ->get();

        $this->assertCount(1, $auditLogs, 'يجب أن ينشئ التحويل الناجح مدخلاً واحداً في سجل التدقيق');

        $auditEntry = $auditLogs->first();
        $this->assertEquals($sender->id, $auditEntry->user_id);
        $this->assertEquals('success', $auditEntry->result);
        $this->assertEquals(2000, $auditEntry->metadata['amount_fils']);
        $this->assertEquals($entry->debit_wallet_id, $auditEntry->metadata['from_wallet_id']);
        $this->assertEquals($entry->credit_wallet_id, $auditEntry->metadata['to_wallet_id']);
        $this->assertEquals($receiver->id, $auditEntry->metadata['to_user_id']);
    }

    public function test_transfer_creates_notification_for_sender(): void
    {
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

        $engine = app(\App\Modules\Ledger\Services\CoreFinancialEngine::class);
        $money = \App\Modules\Core\ValueObjects\Money::fromFils(1500);
        $entry = $engine->transfer(
            amount: $money,
            from: $senderWallet,
            to: $receiverWallet,
            description: 'اختبار الإشعارات',
            referenceType: 'transfer',
            referenceId: 'INT-TEST-NOTIF-001',
            fromUserId: $sender->id,
            toUserId: $receiver->id,
        );

        $notifications = NotificationMessage::where('user_id', $sender->id)
            ->where('reference_id', $entry->id)
            ->get();

        $this->assertGreaterThanOrEqual(1, $notifications->count(),
            'يجب أن ينشئ التحويل إشعاراً للمرسل');

        $notif = $notifications->first();
        $this->assertEquals('transfer_sent', $notif->type);
        $this->assertStringContainsString('1.5', $notif->body);
        $this->assertEquals('ledger_entry', $notif->reference_type);
    }

    public function test_transfer_updates_balances_and_creates_ledger_entry(): void
    {
        $sender = User::factory()->create();
        $senderWallet = Wallet::factory()->create([
            'user_id' => $sender->id,
            'balance_fils' => 50000,
        ]);

        $receiver = User::factory()->create();
        $receiverWallet = Wallet::factory()->create([
            'user_id' => $receiver->id,
            'balance_fils' => 10000,
        ]);

        $engine = app(\App\Modules\Ledger\Services\CoreFinancialEngine::class);
        $money = \App\Modules\Core\ValueObjects\Money::fromFils(7500);
        $entry = $engine->transfer(
            amount: $money,
            from: $senderWallet,
            to: $receiverWallet,
            description: 'اختبار الاتساق',
            referenceType: 'test',
            referenceId: 'CONSISTENCY-001',
            fromUserId: $sender->id,
            toUserId: $receiver->id,
        );

        $senderWallet->refresh();
        $receiverWallet->refresh();

        $this->assertEquals(42500, $senderWallet->balance_fils);
        $this->assertEquals(17500, $receiverWallet->balance_fils);

        $ledgerEntry = LedgerEntry::find($entry->id);
        $this->assertNotNull($ledgerEntry);
        $this->assertEquals($senderWallet->id, $ledgerEntry->debit_wallet_id);
        $this->assertEquals($receiverWallet->id, $ledgerEntry->credit_wallet_id);
        $this->assertEquals(7500, $ledgerEntry->amount_fils);
        $this->assertEquals(50000, $ledgerEntry->metadata['from_balance_before']);
        $this->assertEquals(42500, $ledgerEntry->metadata['from_balance_after']);
        $this->assertEquals(10000, $ledgerEntry->metadata['to_balance_before']);
        $this->assertEquals(17500, $ledgerEntry->metadata['to_balance_after']);
    }
}
