# 09 - SettlementService كامل

```php
<?php
namespace App\Services\Merchant;
use App\Events\SettlementRequested;
use App\Exceptions\MinimumSettlementNotMetException;
use App\Exceptions\PendingSettlementExistsException;
use App\Models\Merchant;
use App\Models\MerchantSettlement;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettlementService
{
    public function __construct(private readonly MerchantWalletService $walletService) {}

    private const MIN_SETTLEMENT_USD = 50;
    private const FEE_PERCENTAGE = 2.00;  // رسوم Beza
    private const TRANSFER_FEE_PERCENTAGE = 1.00;  // رسوم تحويل بنكي

    public function requestSettlement(Merchant $merchant, string $currency): MerchantSettlement
    {
        // 1. التحقق من عدم وجود تسوية معلقة
        $pending = MerchantSettlement::where('merchant_id', $merchant->id)
            ->where('currency', $currency)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
        if ($pending) throw new PendingSettlementExistsException();

        // 2. حساب المبلغ
        $calc = $this->calculateSettlement($merchant, $currency);
        if ($calc['net_amount'] < self::MIN_SETTLEMENT_USD && $currency === 'USD') {
            throw new MinimumSettlementNotMetException(self::MIN_SETTLEMENT_USD);
        }

        // 3. التنفيذ الذري
        $settlement = DB::transaction(function () use ($merchant, $currency, $calc) {
            $wallet = $this->walletService->getWallet($merchant->id, $currency);
            if (!$wallet || $wallet->available_balance < $calc['net_amount']) {
                throw new \RuntimeException('رصيد غير كافٍ للتسوية');
            }

            $this->walletService->decrement($wallet, $calc['net_amount']);

            return MerchantSettlement::create([
                'merchant_id'    => $merchant->id,
                'amount'         => $calc['total_sales'],
                'fee_percentage' => self::FEE_PERCENTAGE,
                'transfer_fee'   => $calc['transfer_fee'],
                'refunds_deducted' => $calc['refunds'],
                'net_amount'     => $calc['net_amount'],
                'currency'       => $currency,
                'status'         => 'pending',
                'bank_account_info' => $merchant->bank_account_info,
            ]);
        }, attempts: 3);

        try { SettlementRequested::dispatch($settlement); }
        catch (\Throwable $e) { Log::warning('فشل إرسال حدث التسوية', ['settlement_id' => $settlement->id]); }

        return $settlement;
    }

    public function calculateSettlement(Merchant $merchant, string $currency): array
    {
        // حساب المبيعات (معاملات merchant_payment)
        $totalSales = Transaction::where('to_wallet_id', function ($q) use ($merchant, $currency) {
            $q->select('id')->from('merchant_wallets')
                ->where('merchant_id', $merchant->id)->where('currency', $currency);
        })->where('type', 'merchant_payment')->where('status', 'completed')->sum('amount');

        // حساب المرتجعات
        $refunds = Transaction::where('from_wallet_id', function ($q) use ($merchant, $currency) {
            $q->select('id')->from('merchant_wallets')
                ->where('merchant_id', $merchant->id)->where('currency', $currency);
        })->where('type', 'refund')->where('status', 'completed')->sum('amount');

        $bezaFee = $totalSales * (self::FEE_PERCENTAGE / 100);
        $transferFee = ($totalSales - $bezaFee - $refunds) * (self::TRANSFER_FEE_PERCENTAGE / 100);
        $netAmount = $totalSales - $bezaFee - $refunds - $transferFee;

        return [
            'total_sales'   => round($totalSales, 2),
            'beza_fee'      => round($bezaFee, 2),
            'refunds'       => round($refunds, 2),
            'transfer_fee'  => round($transferFee, 2),
            'net_amount'    => round($netAmount, 2),
            'currency'      => $currency,
        ];
    }
}
```
