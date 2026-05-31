<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Listeners;

use App\Modules\Merchant\Events\AlertGenerated;
use App\Modules\Merchant\Events\InvoicePaymentInitiated;
use App\Modules\Merchant\Models\Invoice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final class FraudPatternListener
{
    private const REPEAT_ATTEMPT_KEY = 'fraud_repeat_';
    private const REPEAT_WINDOW = 300;
    private const MAX_ATTEMPTS = 3;

    public function handle(InvoicePaymentInitiated $event): void
    {
        $invoice = Invoice::where('invoice_id', $event->invoiceId)->first();

        if (!$invoice) {
            return;
        }

        $cacheKey = self::REPEAT_ATTEMPT_KEY . $event->invoiceId;
        $attempts = (int) Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $attempts, self::REPEAT_WINDOW);

        if ($attempts > self::MAX_ATTEMPTS) {
            Event::dispatch(new AlertGenerated(
                type: 'FRAUD_REPEAT_PAYMENT_ATTEMPT',
                invoiceId: $event->invoiceId,
                details: "Invoice {$event->invoiceId} received {$attempts} payment attempts in " . self::REPEAT_WINDOW . 's',
            ));

            Log::channel('audit')->warning('FRAUD_REPEAT_ATTEMPT', [
                'invoice_id' => $event->invoiceId,
                'attempts' => $attempts,
            ]);
        }

        if ($invoice->status === 'PAID' && $attempts > 1) {
            Event::dispatch(new AlertGenerated(
                type: 'FRAUD_POST_PAYMENT_ATTEMPT',
                invoiceId: $event->invoiceId,
                details: "Post-payment attempt on already paid invoice {$event->invoiceId}",
            ));
        }
    }
}
