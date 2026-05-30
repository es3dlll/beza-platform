<?php

declare(strict_types=1);

namespace Modules\FX\Services;

use Modules\FX\DTOs\GetQuoteDto;
use Modules\FX\Events\FxQuoteCreated;
use Modules\FX\Events\FxQuoteExpired;
use Modules\FX\Exceptions\FxAmountBelowMinimumException;
use Modules\FX\Exceptions\FxRateStaleException;
use Modules\FX\Exceptions\FxRateUnavailableException;
use Modules\FX\Models\FxQuote;
use Modules\FX\Repositories\FxQuoteRepository;
use Modules\FX\Repositories\FxRateRepository;
use Illuminate\Support\Str;

final class FxQuoteService
{
    private const MIN_AMOUNT_SYP = 50000;
    private const MIN_AMOUNT_USD = 10;
    private const RATE_STALE_MINUTES = 5;
    private const DEFAULT_TTL = 60;

    public function __construct(
        private readonly FxQuoteRepository $quotes,
        private readonly FxRateRepository $rates,
    ) {}

    public function generate(GetQuoteDto $dto): FxQuote
    {
        $this->validateMinimum($dto->baseCurrency, $dto->amount);

        $rate = $this->rates->findActive($dto->baseCurrency, $dto->quoteCurrency, $dto->rateType);
        if (!$rate) {
            throw new FxRateUnavailableException($dto->baseCurrency, $dto->quoteCurrency, $dto->rateType);
        }

        if ($rate->published_at && $rate->published_at->diffInMinutes(now()) > self::RATE_STALE_MINUTES) {
            throw new FxRateStaleException($rate->pair(), (int) $rate->published_at->diffInMinutes(now()));
        }

        $isBuyingBase = $dto->baseCurrency === 'USD';
        $customerRate = $isBuyingBase ? (float) $rate->ask_rate : (float) $rate->bid_rate;
        $amountInQuote = (int) round($dto->amount * $customerRate);

        $ttl = $dto->ttlSeconds ?? self::DEFAULT_TTL;

        $quote = new FxQuote();
        $quote->id = Str::ulid()->toBase32();
        $quote->requestor_id = $dto->requestorId;
        $quote->requestor_type = $dto->requestorType;
        $quote->base_currency = strtoupper($dto->baseCurrency);
        $quote->quote_currency = strtoupper($dto->quoteCurrency);
        $quote->amount_in_base = $dto->amount;
        $quote->amount_in_quote = $amountInQuote;
        $quote->rate_used = $customerRate;
        $quote->rate_type = $dto->rateType;
        $quote->fx_rate_id = $rate->id;
        $quote->status = 'active';
        $quote->ttl_seconds = $ttl;
        $quote->expires_at = now()->addSeconds($ttl);

        $this->quotes->save($quote);

        event(new FxQuoteCreated(
            quoteId: $quote->id,
            requestorId: $dto->requestorId,
            requestorType: $dto->requestorType,
            baseCurrency: $quote->base_currency,
            quoteCurrency: $quote->quote_currency,
            amountInBase: $quote->amount_in_base,
            amountInQuote: $quote->amount_in_quote,
            rateUsed: $quote->rate_used,
        ));

        return $quote;
    }

    public function findActive(string $quoteId): ?FxQuote
    {
        return $this->quotes->findActive($quoteId);
    }

    public function getHistory(string $requestorId, string $requestorType): array
    {
        return $this->quotes->findByRequestor($requestorId, $requestorType)->toArray();
    }

    public function expireStaleQuotes(): int
    {
        $expired = $this->quotes->findExpired();
        $count = 0;

        foreach ($expired as $quote) {
            $quote->markExpired();
            $this->quotes->save($quote);
            event(new FxQuoteExpired(quoteId: $quote->id));
            $count++;
        }

        return $count;
    }

    private function validateMinimum(string $currency, int $amount): void
    {
        $minimum = strtoupper($currency) === 'SYP' ? self::MIN_AMOUNT_SYP : self::MIN_AMOUNT_USD;
        if ($amount < $minimum) {
            throw new FxAmountBelowMinimumException($amount, $minimum);
        }
    }
}
