<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Services\NotificationDispatcher;

final class SendNotificationOnEvent
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function handle(object $event): void
    {
        $mapping = $this->getMapping();
        $eventClass = $event::class;
        if (!isset($mapping[$eventClass])) return;

        $cfg = $mapping[$eventClass];
        $userId = $cfg['user_id']($event);
        if (!$userId) return;

        $this->dispatcher->sendMultiChannel(
            userId: $userId,
            type: $cfg['type'],
            title: $cfg['title']($event),
            body: $cfg['body']($event),
            channels: $cfg['channels'] ?? ['in_app'],
            referenceType: $cfg['ref_type'] ?? null,
            referenceId: $cfg['ref_id']($event) ?? null,
        );
    }

    private function getMapping(): array
    {
        return [
            \App\Modules\Ledger\Events\TransferCompleted::class => [
                'user_id' => fn($e) => $e->fromUserId,
                'type' => 'transfer_sent',
                'title' => fn($e) => 'تم تحويل الأموال',
                'body' => fn($e) => 'تم تحويل مبلغ ' . ($e->amount->fils() / 1000) . ' ل.س من محفظتك بنجاح.',
                'channels' => ['in_app'],
                'ref_type' => 'ledger_entry',
                'ref_id' => fn($e) => $e->entry->id,
            ],
            \App\Modules\Remittance\Events\RemittanceCompleted::class => [
                'user_id' => fn($e) => $e->remittance->user_id ?? null,
                'type' => 'remittance_completed',
                'title' => fn($e) => 'تم تحويل الأموال بنجاح',
                'body' => fn($e) => 'تحويل بقيمة ' . ($e->amount?->format() ?? '') . ' إلى ' . ($e->remittance->recipient_name ?? '') . ' تم بنجاح.',
                'channels' => ['in_app', 'email'],
                'ref_type' => 'remittance',
                'ref_id' => fn($e) => $e->remittance->id ?? null,
            ],
            \App\Modules\Remittance\Events\RemittanceApproved::class => [
                'user_id' => fn($e) => $e->remittance->user_id ?? null,
                'type' => 'remittance_approved',
                'title' => fn($e) => 'تمت الموافقة على التحويل',
                'body' => fn($e) => 'تمت الموافقة على تحويلك. سيتم التنفيذ قريباً.',
                'channels' => ['in_app'],
                'ref_type' => 'remittance',
                'ref_id' => fn($e) => $e->remittance->id ?? null,
            ],
            \App\Modules\Fraud\Events\FraudAlertTriggered::class => [
                'user_id' => fn($e) => $e->agent->user_id ?? $e->userId ?? null,
                'type' => 'fraud_alert',
                'title' => fn($e) => 'تنبيه: عملية بحاجة لمراجعة',
                'body' => fn($e) => 'تم تعليق معاملة بقيمة ' . (isset($e->amountFils) ? ($e->amountFils / 1000) . ' ل.س' : '') . ' للمراجعة.',
                'channels' => ['in_app', 'email'],
                'ref_type' => 'fraud',
                'ref_id' => fn($e) => $e->requestId ?? null,
            ],
            \App\Modules\Bills\Events\BillPaymentDue::class => [
                'user_id' => fn($e) => $e->bill->user_id ?? null,
                'type' => 'bill_due',
                'title' => fn($e) => 'فاتورة مستحقة',
                'body' => fn($e) => 'فاتورة ' . ($e->bill->provider_name ?? '') . ' مستحقة بمبلغ ' . ($e->bill->amount_fils / 1000) . ' ل.س',
                'channels' => ['in_app', 'email', 'sms'],
                'ref_type' => 'bill',
                'ref_id' => fn($e) => $e->bill->id ?? null,
            ],
            \App\Modules\Escrow\Events\EscrowDisputed::class => [
                'user_id' => fn($e) => $e->transaction->buyer_id ?? null,
                'type' => 'escrow_disputed',
                'title' => fn($e) => 'تم فتح نزاع',
                'body' => fn($e) => 'تم فتح نزاع على معاملة بقيمة ' . ($e->transaction->amount_fils / 1000) . ' ل.س. سيتم مراجعته قريباً.',
                'channels' => ['in_app', 'email'],
                'ref_type' => 'escrow',
                'ref_id' => fn($e) => $e->transaction->id ?? null,
            ],
            \App\Modules\Escrow\Events\EscrowReleased::class => [
                'user_id' => fn($e) => $e->transaction->seller_id ?? null,
                'type' => 'escrow_released',
                'title' => fn($e) => 'تم إطلاق الدفعة',
                'body' => fn($e) => 'تم إطلاق دفعة بقيمة ' . ($e->transaction->amount_fils / 1000) . ' ل.س إلى محفظتك.',
                'channels' => ['in_app', 'email'],
                'ref_type' => 'escrow',
                'ref_id' => fn($e) => $e->transaction->id ?? null,
            ],
            \App\Modules\Escrow\Events\EscrowRefunded::class => [
                'user_id' => fn($e) => $e->transaction->buyer_id ?? null,
                'type' => 'escrow_refunded',
                'title' => fn($e) => 'تم إرجاع المبلغ',
                'body' => fn($e) => 'تم إرجاع مبلغ ' . ($e->transaction->amount_fils / 1000) . ' ل.س إلى محفظتك.',
                'channels' => ['in_app', 'email'],
                'ref_type' => 'escrow',
                'ref_id' => fn($e) => $e->transaction->id ?? null,
            ],
        ];
    }
}
