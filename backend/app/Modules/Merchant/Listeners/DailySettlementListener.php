<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Listeners;

use App\Modules\Merchant\Enums\SettlementCycle;
use App\Modules\Merchant\Events\TriggerMerchantSettlement;
use App\Modules\Merchant\Models\Invoice;
use App\Modules\Merchant\Models\Merchant;
use Illuminate\Support\Facades\Log;

final class DailySettlementListener
{
    public function handle(TriggerMerchantSettlement $event): void
    {
        $merchant = Merchant::where('merchant_id', $event->merchantId)->first();

        if (!$merchant) {
            return;
        }

        $window = SettlementCycle::settlementWindow($event->settlementCycle);
        $cutoff = now()->subHours($window);

        $pendingInvoices = Invoice::where('merchant_id', $event->merchantId)
            ->where('status', 'PAID')
            ->whereNull('settlement_status')
            ->when($window > 0, fn ($q) => $q->where('paid_at', '<=', $cutoff))
            ->get();

        if ($pendingInvoices->isEmpty()) {
            return;
        }

        $totalAmount = $pendingInvoices->sum('total_amount');
        $totalTax = $pendingInvoices->sum('tax_amount');
        $commissionAmount = intdiv($totalAmount * $merchant->commission_bps, 10000);
        $netAmount = $totalAmount - $commissionAmount;

        Invoice::whereIn('id', $pendingInvoices->pluck('id'))
            ->update(['settlement_status' => 'settled']);

        Log::channel('audit')->info('MERCHANT_SETTLEMENT', [
            'merchant_id' => $event->merchantId,
            'cycle' => $event->settlementCycle,
            'invoices_count' => $pendingInvoices->count(),
            'total_amount' => $totalAmount,
            'commission' => $commissionAmount,
            'net_amount' => $netAmount,
        ]);
    }
}
