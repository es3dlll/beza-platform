# 10 - CommodityService كامل (Core Service Layer)

## PriceFeedProvider

```php
<?php
// app/Services/PriceFeedProvider.php

namespace App\Services;

use App\Exceptions\MarketClosedException;
use App\Exceptions\SpreadTooHighException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PriceFeedProvider
{
    const CACHE_TTL = 30; // ثانية

    const GOLD_API_URL   = 'https://api.gold-api.com/price/XAU';
    const SILVER_API_URL = 'https://api.gold-api.com/price/XAG';

    /**
     * الحصول على سعر سلعة (ذهبية أو فضية)
     *
     * @return array{price_usd: float, price_syp: float, bid: float, ask: float, timestamp: string}
     */
    public function getPrice(string $commodity): array
    {
        $cacheKey = "commodity_price_{$commodity}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($commodity) {
            $url = $commodity === 'gold' ? self::GOLD_API_URL : self::SILVER_API_URL;

            try {
                $response = Http::timeout(5)->get($url);

                if ($response->failed()) {
                    Log::warning('فشل جلب سعر السلعة', ['commodity' => $commodity]);
                    return $this->getLastKnownPrice($commodity);
                }

                $data = $response->json();

                $priceUsd = (float) ($data['price'] ?? 0);
                $bid      = round($priceUsd * 0.995, 2); // spread 0.5%
                $ask      = round($priceUsd * 1.015, 2); // premium 1.5%

                $this->checkSpread($bid, $ask);

                $result = [
                    'price_usd' => $priceUsd,
                    'price_syp' => round($priceUsd * 13000, 2),
                    'bid'       => $bid,
                    'ask'       => $ask,
                    'timestamp' => now()->toIso8601String(),
                ];

                // تسجيل السعر في قاعدة البيانات للتدقيق
                \App\Models\CommodityPrice::create([
                    'commodity' => $commodity,
                    'price_usd' => $result['price_usd'],
                    'price_syp' => $result['price_syp'],
                    'bid_usd'   => $result['bid'],
                    'ask_usd'   => $result['ask'],
                    'source'    => 'gold-api.com',
                    'timestamp' => now(),
                ]);

                return $result;

            } catch (\Throwable $e) {
                Log::error('خطأ في جلب السعر', [
                    'commodity' => $commodity,
                    'error'     => $e->getMessage(),
                ]);
                return $this->getLastKnownPrice($commodity);
            }
        });
    }

    /**
     * الحصول على آخر سعر معروف من قاعدة البيانات
     */
    private function getLastKnownPrice(string $commodity): array
    {
        $lastPrice = \App\Models\CommodityPrice::ofCommodity($commodity)
            ->latest()
            ->first();

        if ($lastPrice) {
            return [
                'price_usd' => (float) $lastPrice->price_usd,
                'price_syp' => (float) $lastPrice->price_syp,
                'bid'       => (float) $lastPrice->bid_usd,
                'ask'       => (float) $lastPrice->ask_usd,
                'timestamp' => $lastPrice->timestamp->toIso8601String(),
            ];
        }

        // أسعار افتراضية احتياطية (عند أول تشغيل)
        $default = $commodity === 'gold'
            ? ['price_usd' => 2300.00, 'bid' => 2288.50, 'ask' => 2334.50]
            : ['price_usd' => 27.50,   'bid' => 27.36,   'ask' => 27.91];

        return [
            'price_usd' => $default['price_usd'],
            'price_syp' => round($default['price_usd'] * 13000, 2),
            'bid'       => $default['bid'],
            'ask'       => $default['ask'],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * الحصول على جميع الأسعار (ذهب + فضة)
     */
    public function getAllPrices(): array
    {
        return [
            'gold'   => $this->getPrice('gold'),
            'silver' => $this->getPrice('silver'),
        ];
    }

    /**
     * التحقق من أن السوق مفتوح
     * سوق الذهب العالمي مفتوح من الأحد 23:00 إلى الجمعة 22:00 (GMT)
     */
    public function isMarketOpen(): bool
    {
        $now = Carbon::now('GMT');

        // الجمعة بعد 22:00 → مغلق
        if ($now->dayOfWeek === Carbon::FRIDAY && $now->hour >= 22) {
            return false;
        }

        // السبت ← مغلق
        if ($now->dayOfWeek === Carbon::SATURDAY) {
            return false;
        }

        // الأحد قبل 23:00 ← مغلق
        if ($now->dayOfWeek === Carbon::SUNDAY && $now->hour < 23) {
            return false;
        }

        return true;
    }

    /**
     * التحقق من هامش السبريد
     * spread = (ask - bid) / ask * 100
     */
    private function checkSpread(float $bid, float $ask): void
    {
        if ($ask <= 0) return;

        $spreadPercent = (($ask - $bid) / $ask) * 100;

        if ($spreadPercent > 5.0) {
            throw new SpreadTooHighException($spreadPercent);
        }
    }

    /**
     * التحقق من أن السعر لا يزال "طازجاً" (ضمن 30 ثانية)
     */
    public function ensurePriceFresh(string $timestamp): void
    {
        $priceTime = Carbon::parse($timestamp);
        $diffInSeconds = $priceTime->diffInSeconds(now());

        if ($diffInSeconds > 30) {
            throw new \App\Exceptions\PriceExpiredException($diffInSeconds);
        }
    }
}
```

## CommodityService

```php
<?php
// app/Services/CommodityService.php

namespace App\Services;

use App\Events\GoldPurchased;
use App\Events\GoldSold;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InsufficientHoldingException;
use App\Exceptions\MarketClosedException;
use App\Exceptions\MinimumHoldingPeriodException;
use App\Models\CommodityHolding;
use App\Models\CommodityTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommodityService
{
    public function __construct(
        private readonly WalletService     $walletService,
        private readonly PriceFeedProvider $priceFeed,
    ) {}

    /**
     * تنفيذ عملية شراء الذهب/الفضة
     *
     * @param User   $user
     * @param string $commodity  'gold' | 'silver'
     * @param float  $amountSpent  المبلغ الذي ينفقه المستخدم
     * @param string $currency   'SYP' | 'USD'
     *
     * @return array{grams: float, price_per_gram: float, total_spent: float, fee: float, ...}
     *
     * @throws MarketClosedException
     * @throws \App\Exceptions\PriceExpiredException
     * @throws InsufficientBalanceException
     * @throws \Throwable
     */
    public function executeBuy(
        User   $user,
        string $commodity,
        float  $amountSpent,
        string $currency,
    ): array {

        // ─── 1. التحقق من فتح السوق ───
        if (!$this->priceFeed->isMarketOpen()) {
            throw new MarketClosedException();
        }

        // ─── 2. الحصول على السعر ───
        $price = $this->priceFeed->getPrice($commodity);

        // ─── 3. حساب الجرامات ───
        // يتم استخدام سعر ask (سعر البيع للمنصة)
        $pricePerGram = $price['ask'];

        // تحويل المبلغ إلى USD إذا كان SYP
        $amountInUsd = $currency === 'USD'
            ? $amountSpent
            : $this->walletService->convertToUsd($amountSpent);

        $feePercent = 0.015; // 1.5% رسوم
        $fee        = round($amountInUsd * $feePercent, 2);
        $netAmount  = $amountInUsd - $fee;
        $grams      = round($netAmount / $pricePerGram, 4);

        if ($grams <= 0) {
            throw new \InvalidArgumentException('المبلغ غير كافٍ لشراء حتى 0.0001 جرام');
        }

        // ─── 4. التحقق من المحفظة ───
        $wallet = $this->walletService->getWallet($user->id, $currency);
        $this->walletService->ensureActive($wallet);

        // ─── 5. التنفيذ الذري ───
        $result = DB::transaction(function () use (
            $user, $commodity, $grams, $amountInUsd, $fee,
            $netAmount, $pricePerGram, $currency, $wallet
        ) {
            // 5a. قفل المحفظة
            $this->walletService->lockForUpdate($wallet->id);

            // 5b. قفل صف الحيازة (إذا وجد)
            $holding = CommodityHolding::where('user_id', $user->id)
                ->where('commodity', $commodity)
                ->lockForUpdate()
                ->first();

            // 5c. خصم من المحفظة
            $this->walletService->decrement($wallet, $amountInUsd);

            // 5d. إنشاء أو تحديث الحيازة
            if ($holding) {
                // حساب متوسط السعر المرجح
                $totalGrams   = $holding->grams + $grams;
                $totalInvested = $holding->total_invested_usd + $amountInUsd;
                $avgPrice     = round($totalInvested / $totalGrams, 2);

                $holding->update([
                    'grams'              => $totalGrams,
                    'avg_price_usd'      => $avgPrice,
                    'total_invested_usd' => $totalInvested,
                ]);
            } else {
                $holding = CommodityHolding::create([
                    'user_id'            => $user->id,
                    'commodity'          => $commodity,
                    'grams'              => $grams,
                    'avg_price_usd'      => $pricePerGram,
                    'total_invested_usd' => $amountInUsd,
                ]);
            }

            // 5e. تسجيل المعاملة
            $txn = CommodityTransaction::create([
                'user_id'         => $user->id,
                'commodity'       => $commodity,
                'type'            => 'buy',
                'grams'           => $grams,
                'price_usd'       => $pricePerGram,
                'total_usd'       => $amountInUsd,
                'fee'             => $fee,
                'reference_number'=> CommodityTransaction::generateReferenceNumber(),
                'status'          => 'completed',
            ]);

            return [
                'holding' => $holding,
                'txn'     => $txn,
            ];
        }, attempts: 3);

        // ─── 6. إرسال الحدث (Async) ───
        try {
            GoldPurchased::dispatch($result['txn'], $user);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال حدث GoldPurchased', [
                'txn_id' => $result['txn']->id,
                'error'  => $e->getMessage(),
            ]);
        }

        // ─── 7. تحديث الرصيد ───
        $wallet->refresh();

        return [
            'grams'           => $grams,
            'price_per_gram'  => $pricePerGram,
            'total_spent'     => $amountInUsd,
            'fee'             => $fee,
            'commodity'       => $commodity,
            'commodity_name'  => $commodity === 'gold' ? 'ذهب' : 'فضة',
            'holding'         => $result['holding'],
            'new_balance'     => (float) $wallet->balance,
            'reference_number'=> $result['txn']->reference_number,
        ];
    }

    /**
     * تنفيذ عملية بيع الذهب/الفضة
     *
     * @param User   $user
     * @param string $commodity
     * @param float  $grams       عدد الجرامات المراد بيعها
     * @param string $currency    العملة المستلمة
     *
     * @return array
     *
     * @throws InsufficientHoldingException
     * @throws MinimumHoldingPeriodException
     * @throws MarketClosedException
     * @throws \Throwable
     */
    public function executeSell(
        User   $user,
        string $commodity,
        float  $grams,
        string $currency,
    ): array {

        // ─── 1. التحقق من فتح السوق ───
        if (!$this->priceFeed->isMarketOpen()) {
            throw new MarketClosedException();
        }

        // ─── 2. الحصول على الحيازة ───
        $holding = CommodityHolding::where('user_id', $user->id)
            ->where('commodity', $commodity)
            ->firstOrFail();

        if ($holding->grams < $grams) {
            throw new InsufficientHoldingException(
                available: (float) $holding->grams,
                requested: $grams,
            );
        }

        // ─── 3. التحقق من فترة الاحتفاظ الدنيا (24 ساعة) ───
        $lastBuyTransaction = CommodityTransaction::where('user_id', $user->id)
            ->where('commodity', $commodity)
            ->where('type', 'buy')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastBuyTransaction && $lastBuyTransaction->created_at->diffInHours(now()) < 24) {
            throw new MinimumHoldingPeriodException(
                hoursRemaining: 24 - $lastBuyTransaction->created_at->diffInHours(now()),
            );
        }

        // ─── 4. الحصول على السعر (bid = سعر الشراء من المستخدم) ───
        $price = $this->priceFeed->getPrice($commodity);
        $pricePerGram = $price['bid'];

        $feePercent = 0.01; // 1% رسوم بيع
        $totalReceived = round($grams * $pricePerGram, 2);
        $fee = round($totalReceived * $feePercent, 2);
        $netReceived = $totalReceived - $fee;

        // تحويل إلى العملة المطلوبة
        $amountInCurrency = $currency === 'USD'
            ? $netReceived
            : $this->walletService->convertToSyp($netReceived);

        // ─── 5. التنفيذ الذري ───
        $result = DB::transaction(function () use (
            $user, $commodity, $grams, $holding, $pricePerGram,
            $totalReceived, $fee, $netReceived, $currency
        ) {
            $wallet = $this->walletService->getWallet($user->id, $currency);
            $this->walletService->ensureActive($wallet);
            $this->walletService->lockForUpdate($wallet->id);

            // قفل صف الحيازة
            CommodityHolding::where('id', $holding->id)
                ->lockForUpdate()
                ->first();

            // خصم الجرامات
            $newGrams = $holding->grams - $grams;
            $newInvested = round(
                $holding->total_invested_usd * ($newGrams / $holding->grams),
                2
            );

            $holding->update([
                'grams'              => round($newGrams, 4),
                'total_invested_usd' => $newInvested,
            ]);

            // إضافة الرصيد
            $this->walletService->increment($wallet, $amountInCurrency);

            // تسجيل المعاملة
            $txn = CommodityTransaction::create([
                'user_id'         => $user->id,
                'commodity'       => $commodity,
                'type'            => 'sell',
                'grams'           => $grams,
                'price_usd'       => $pricePerGram,
                'total_usd'       => $totalReceived,
                'fee'             => $fee,
                'reference_number'=> CommodityTransaction::generateReferenceNumber(),
                'status'          => 'completed',
            ]);

            return [
                'holding' => $holding,
                'txn'     => $txn,
                'wallet'  => $wallet,
            ];
        }, attempts: 3);

        // ─── 6. إرسال الحدث ───
        try {
            GoldSold::dispatch($result['txn'], $user);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال حدث GoldSold', [
                'txn_id' => $result['txn']->id,
                'error'  => $e->getMessage(),
            ]);
        }

        return [
            'grams'           => $grams,
            'price_per_gram'  => $pricePerGram,
            'total_received'  => $totalReceived,
            'fee'             => $fee,
            'net_received'    => $netReceived,
            'commodity'       => $commodity,
            'commodity_name'  => $commodity === 'gold' ? 'ذهب' : 'فضة',
            'holding'         => $result['holding'],
            'new_balance'     => (float) $result['wallet']->fresh()->balance,
            'reference_number'=> $result['txn']->reference_number,
        ];
    }
}
```

## تدفق CommodityService خطوة بخطوة

```
executeBuy():
  1. isMarketOpen()              → MarketClosedException
  2. getPrice(commodity)         → {bid, ask, timestamp}
  3. حساب الجرامات (amount - fee) / ask
  4. getWallet() + ensureActive()
  5. DB::transaction {
       lockForUpdate(wallet)
       lockForUpdate(holding)    ← Pessimistic Lock
       decrement(wallet)
       upsert(holding)           ← Create or Update
       create(transaction)
     }
  6. dispatch(GoldPurchased)
  7. Return result

executeSell():
  1. isMarketOpen()              → MarketClosedException
  2. getHolding()
  3. holding.grams >= requested  → InsufficientHoldingException
  4. 24h check                   → MinimumHoldingPeriodException
  5. getPrice(commodity)         → {bid}
  6. حساب القيمة grams * bid
  7. DB::transaction {
       lockForUpdate(wallet)
       lockForUpdate(holding)
       update(holding grams -=)
       increment(wallet)
       create(transaction)
     }
  8. dispatch(GoldSold)
  9. Return result
```
