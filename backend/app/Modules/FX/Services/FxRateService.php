<?php

declare(strict_types=1);

namespace Modules\FX\Services;

use Modules\FX\DTOs\CreateFxRateDto;
use Modules\FX\Events\FxRateUpdated;
use Modules\FX\Exceptions\FxInvalidPairException;
use Modules\FX\Models\FxRate;
use Modules\FX\Repositories\FxRateRepository;
use Illuminate\Support\Str;

final class FxRateService
{
    private const SUPPORTED_PAIRS = [
        ['base' => 'USD', 'quote' => 'SYP'],
        ['base' => 'SYP', 'quote' => 'USD'],
    ];

    public function __construct(
        private readonly FxRateRepository $rates,
    ) {}

    public function create(CreateFxRateDto $dto): FxRate
    {
        $this->validatePair($dto->baseCurrency, $dto->quoteCurrency);

        $spread = $dto->spreadPct ?? 0;
        $bid = $dto->bidRate ?? round($dto->midRate * (1 - $spread / 100), 6);
        $ask = $dto->askRate ?? round($dto->midRate * (1 + $spread / 100), 6);

        $rate = new FxRate();
        $rate->id = Str::ulid()->toBase32();
        $rate->base_currency = strtoupper($dto->baseCurrency);
        $rate->quote_currency = strtoupper($dto->quoteCurrency);
        $rate->bid_rate = $bid;
        $rate->mid_rate = $dto->midRate;
        $rate->ask_rate = $ask;
        $rate->spread_pct = $spread;
        $rate->rate_type = $dto->rateType;
        $rate->source = $dto->source;
        $rate->valid_from = $dto->validFrom ?? now();
        $rate->valid_to = $dto->validTo;
        $rate->published_at = now();

        $this->rates->save($rate);

        event(new FxRateUpdated(
            rateId: $rate->id,
            baseCurrency: $rate->base_currency,
            quoteCurrency: $rate->quote_currency,
            midRate: (float) $rate->mid_rate,
            rateType: $rate->rate_type,
        ));

        return $rate;
    }

    public function getActiveRate(string $baseCurrency, string $quoteCurrency, string $rateType = 'cbs_official'): ?FxRate
    {
        $this->validatePair($baseCurrency, $quoteCurrency);
        return $this->rates->findActive($baseCurrency, $quoteCurrency, $rateType);
    }

    public function getAllActive(): array
    {
        return $this->rates->findAllActive()->toArray();
    }

    public function getRateHistory(string $baseCurrency, string $quoteCurrency): array
    {
        $this->validatePair($baseCurrency, $quoteCurrency);
        return $this->rates->findLatestForPair($baseCurrency, $quoteCurrency)->toArray();
    }

    public function validatePair(string $base, string $quote): void
    {
        $base = strtoupper($base);
        $quote = strtoupper($quote);

        foreach (self::SUPPORTED_PAIRS as $pair) {
            if ($pair['base'] === $base && $pair['quote'] === $quote) {
                return;
            }
        }

        throw new FxInvalidPairException($base, $quote);
    }
}
