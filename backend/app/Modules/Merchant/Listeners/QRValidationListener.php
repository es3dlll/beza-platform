<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Listeners;

use App\Modules\Merchant\Events\InvoicePaymentInitiated;
use App\Modules\Merchant\Events\QRValidationFailed;
use App\Modules\Merchant\Models\Invoice;
use App\Modules\Merchant\Services\QRService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final class QRValidationListener
{
    public function __construct(
        private readonly QRService $qrService,
    ) {}

    public function handle(InvoicePaymentInitiated $event): void
    {
        $invoice = Invoice::where('invoice_id', $event->invoiceId)->first();

        if (!$invoice) {
            Event::dispatch(new QRValidationFailed($event->invoiceId, 'Invoice not found'));
            return;
        }

        $isValid = $this->qrService->validate($event->qrToken, $event->invoiceId);

        if (!$isValid) {
            Event::dispatch(new QRValidationFailed($event->invoiceId, 'QR validation failed - token invalid or expired'));
            Log::channel('audit')->warning('QR_INVALID', [
                'invoice_id' => $event->invoiceId,
                'payee_id' => $event->payeeId,
            ]);
        }
    }
}
