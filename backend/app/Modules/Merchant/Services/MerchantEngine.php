<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Modules\Merchant\Enums\InvoiceStatus;
use App\Modules\Merchant\Enums\SettlementCycle;
use App\Modules\Merchant\Events\InvoiceCreated;
use App\Modules\Merchant\Events\InvoicePaid;
use App\Modules\Merchant\Events\InvoicePaymentInitiated;
use App\Modules\Merchant\Events\RefundRequested;
use App\Modules\Merchant\Events\TriggerMerchantSettlement;
use App\Modules\Merchant\Exceptions\InvalidSettlementCycleException;
use App\Modules\Merchant\Exceptions\InvoiceAlreadyPaidException;
use App\Modules\Merchant\Models\Invoice;
use App\Modules\Merchant\Models\Merchant;
use App\Modules\Merchant\ValueObjects\InvoiceId;
use App\Modules\Merchant\ValueObjects\MerchantId;
use App\Modules\Merchant\ValueObjects\QRToken;
use Illuminate\Support\Facades\Event;

final class MerchantEngine
{
    public function __construct(
        private readonly QRService $qrService,
        private readonly TaxService $taxService,
    ) {}

    public function onboard(array $data): Merchant
    {
        $merchantId = MerchantId::generate()->toString();

        return Merchant::create([
            'merchant_id' => $merchantId,
            'business_name' => $data['business_name'],
            'owner_id' => $data['owner_id'],
            'phone' => $data['phone'],
            'category' => $data['category'] ?? 'goods_general',
            'settlement_cycle' => $data['settlement_cycle'] ?? SettlementCycle::DAILY,
            'commission_bps' => SettlementCycle::commissionBps($data['settlement_cycle'] ?? SettlementCycle::DAILY),
            'status' => 'active',
            'compliance_level' => 'standard',
        ]);
    }

    public function createInvoice(string $merchantId, int $amount, string $description, string $category): array
    {
        $invoiceId = InvoiceId::generate()->toString();
        $tax = $this->taxService->calculateTax($category, $amount);
        $totalAmount = $amount + $tax['tax_amount'];

        $qrToken = $this->qrService->generate($invoiceId, $merchantId, $totalAmount);

        $invoice = Invoice::create([
            'invoice_id' => $invoiceId,
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'tax_amount' => $tax['tax_amount'],
            'total_amount' => $totalAmount,
            'description' => $description,
            'category' => $category,
            'status' => InvoiceStatus::PENDING_PAYMENT,
            'qr_token' => $qrToken->token(),
            'qr_expires_at' => now()->addSeconds($qrToken->remainingSeconds()),
        ]);

        Event::dispatch(new InvoiceCreated(
            invoiceId: $invoiceId,
            merchantId: $merchantId,
            amount: $amount,
            taxAmount: $tax['tax_amount'],
            totalAmount: $totalAmount,
            qrToken: $qrToken->token(),
        ));

        return [
            'invoice_id' => $invoiceId,
            'qr_token' => $qrToken->token(),
            'qr_expires_in' => $qrToken->remainingSeconds(),
            'total_amount' => $totalAmount,
            'tax_amount' => $tax['tax_amount'],
        ];
    }

    public function initiatePayment(string $invoiceId, string $qrToken, string $payeeId): void
    {
        $invoice = Invoice::where('invoice_id', $invoiceId)->firstOrFail();

        if ($invoice->status === InvoiceStatus::PAID) {
            throw new InvoiceAlreadyPaidException($invoiceId);
        }

        InvoiceStatus::assertTransition($invoice->status, InvoiceStatus::PAID);

        if (!$this->qrService->validate($qrToken, $invoiceId)) {
            throw new \App\Modules\Merchant\Exceptions\QRExpiredException('QR code expired or invalid');
        }

        $invoice->update(['status' => InvoiceStatus::PAID]);

        Event::dispatch(new InvoicePaymentInitiated(
            invoiceId: $invoiceId,
            qrToken: $qrToken,
            payeeId: $payeeId,
        ));
    }

    public function confirmPayment(InvoicePaid $event): void
    {
        $invoice = Invoice::where('invoice_id', $event->invoiceId)->first();
        if (!$invoice) {
            return;
        }

        $invoice->updateQuietly([
            'status' => InvoiceStatus::PAID,
            'settlement_status' => 'pending',
            'paid_at' => $event->paidAt,
        ]);
    }

    public function triggerSettlement(string $merchantId, string $cycle): void
    {
        if (!SettlementCycle::isValid($cycle)) {
            throw new InvalidSettlementCycleException($cycle);
        }

        Event::dispatch(new TriggerMerchantSettlement(
            merchantId: $merchantId,
            settlementCycle: $cycle,
        ));
    }

    public function requestRefund(string $invoiceId, string $reason): void
    {
        $invoice = Invoice::where('invoice_id', $invoiceId)->firstOrFail();
        InvoiceStatus::assertTransition($invoice->status, InvoiceStatus::REFUNDED);

        $invoice->update(['status' => InvoiceStatus::REFUNDED]);

        Event::dispatch(new RefundRequested(
            invoiceId: $invoiceId,
            merchantId: $invoice->merchant_id,
            amount: $invoice->total_amount,
            reason: $reason,
        ));
    }
}
