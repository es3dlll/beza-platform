<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Listeners;

use App\Modules\Merchant\Events\InvoicePaid;
use App\Modules\Merchant\Events\TaxRecordCreated;
use App\Modules\Merchant\Models\Invoice;
use App\Modules\Merchant\Services\TaxService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final class TaxComplianceListener
{
    public function __construct(
        private readonly TaxService $taxService,
    ) {}

    public function handle(InvoicePaid $event): void
    {
        $invoice = Invoice::where('invoice_id', $event->invoiceId)->first();

        if (!$invoice) {
            return;
        }

        $tax = $this->taxService->calculateTax(
            category: $invoice->category,
            amount: $invoice->amount,
        );

        Event::dispatch(new TaxRecordCreated(
            invoiceId: $event->invoiceId,
            taxAmount: $tax['tax_amount'],
            taxCategory: $tax['category'],
        ));

        Log::channel('audit')->info('TAX_RECORDED', [
            'invoice_id' => $event->invoiceId,
            'tax_amount' => $tax['tax_amount'],
            'category' => $tax['category'],
            'rate_bps' => $tax['rate_bps'],
        ]);
    }
}
