<?php

declare(strict_types=1);

namespace Modules\FX\Services;

use Modules\FX\DTOs\ExecuteConversionDto;
use Modules\FX\Events\FxConversionCompleted;
use Modules\FX\Events\FxConversionFailed;
use Modules\FX\Events\FxQuoteAccepted;
use Modules\FX\Exceptions\FxAmountExceedsLimitException;
use Modules\FX\Exceptions\FxRateExpiredException;
use Modules\FX\Exceptions\FxRateLockContentionException;
use Modules\FX\Models\FxConversion;
use Modules\FX\Repositories\FxConversionRepository;
use Modules\FX\Repositories\FxQuoteRepository;
use Illuminate\Support\Str;

final class FxConversionService
{
    private const DAILY_LIMIT_TIER_1 = 500;
    private const DAILY_LIMIT_TIER_2 = 5000;
    private const DAILY_LIMIT_TIER_3 = 25000;

    public function __construct(
        private readonly FxConversionRepository $conversions,
        private readonly FxQuoteRepository $quotes,
    ) {}

    public function execute(ExecuteConversionDto $dto): FxConversion
    {
        $quote = $this->quotes->findById($dto->quoteId);
        if (!$quote) {
            throw new FxRateExpiredException($dto->quoteId);
        }

        if (!$quote->isActive()) {
            if ($quote->status === 'accepted') {
                throw new FxRateLockContentionException($dto->quoteId);
            }
            throw new FxRateExpiredException($dto->quoteId);
        }

        $this->checkDailyLimit($dto->fromWalletId ?? $quote->requestor_id, $quote->amount_in_base);

        $quote->accept();
        $this->quotes->save($quote);

        $feeAmount = (int) round($quote->amount_in_base * 0.015);

        $conversion = new FxConversion();
        $conversion->id = Str::ulid()->toBase32();
        $conversion->quote_id = $quote->id;
        $conversion->from_wallet_id = $dto->fromWalletId;
        $conversion->to_wallet_id = $dto->toWalletId;
        $conversion->from_currency = $quote->base_currency;
        $conversion->to_currency = $quote->quote_currency;
        $conversion->from_amount = $quote->amount_in_base;
        $conversion->to_amount = $quote->amount_in_quote;
        $conversion->rate_applied = $quote->rate_used;
        $conversion->fee_amount = $feeAmount;
        $conversion->fee_currency = 'SYP';
        $conversion->status = 'completed';
        $conversion->completed_at = now();

        $this->conversions->save($conversion);

        event(new FxQuoteAccepted(
            quoteId: $quote->id,
            conversionId: $conversion->id,
        ));

        event(new FxConversionCompleted(
            conversionId: $conversion->id,
            quoteId: $quote->id,
            fromCurrency: $quote->base_currency,
            toCurrency: $quote->quote_currency,
            fromAmount: $quote->amount_in_base,
            toAmount: $quote->amount_in_quote,
            feeAmount: $feeAmount,
        ));

        return $conversion;
    }

    public function getConversion(string $id): ?FxConversion
    {
        return $this->conversions->findById($id);
    }

    public function getWalletConversions(string $walletId): array
    {
        return $this->conversions->findByWallet($walletId)->toArray();
    }

    private function checkDailyLimit(string $walletId, int $amountInBase): void
    {
        $todayUsed = $this->conversions->todayTotal($walletId, 'from');
        $limit = self::DAILY_LIMIT_TIER_1 * 100; 

        if (($todayUsed + $amountInBase) > $limit) {
            throw new FxAmountExceedsLimitException($amountInBase, $limit, $todayUsed);
        }
    }
}
