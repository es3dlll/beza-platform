<?php

declare(strict_types=1);

namespace Modules\FX\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FX\DTOs\CreateFxRateDto;
use Modules\FX\DTOs\GetQuoteDto;
use Modules\FX\DTOs\ExecuteConversionDto;
use Modules\FX\Exceptions\FxAmountBelowMinimumException;
use Modules\FX\Exceptions\FxInvalidPairException;
use Modules\FX\Exceptions\FxRateExpiredException;
use Modules\FX\Exceptions\FxRateUnavailableException;
use Modules\FX\Models\FxRate;
use Modules\FX\Models\FxQuote;
use Modules\FX\Models\FxConversion;
use Modules\FX\Services\FxRateService;
use Modules\FX\Services\FxQuoteService;
use Modules\FX\Services\FxConversionService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class FxEngineTest extends TestCase
{
    use RefreshDatabase;

    private FxRateService $rates;
    private FxQuoteService $quotes;
    private FxConversionService $conversions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rates = $this->app->make(FxRateService::class);
        $this->quotes = $this->app->make(FxQuoteService::class);
        $this->conversions = $this->app->make(FxConversionService::class);
    }

    /* ──── Rate Tests ──── */

    public function test_can_create_cbs_rate(): void
    {
        $dto = new CreateFxRateDto(
            baseCurrency: 'USD',
            quoteCurrency: 'SYP',
            midRate: 13100,
            rateType: 'cbs_official',
            source: 'CBS',
        );

        $rate = $this->rates->create($dto);

        $this->assertInstanceOf(FxRate::class, $rate);
        $this->assertEquals('USD', $rate->base_currency);
        $this->assertEquals('SYP', $rate->quote_currency);
        $this->assertEquals(13100, $rate->mid_rate);
        $this->assertEquals('cbs_official', $rate->rate_type);
        $this->assertTrue($rate->isActive());
    }

    public function test_can_create_rate_with_spread(): void
    {
        $dto = new CreateFxRateDto(
            baseCurrency: 'USD',
            quoteCurrency: 'SYP',
            midRate: 13100,
            rateType: 'market',
            source: 'Treasury',
            spreadPct: 1.5,
        );

        $rate = $this->rates->create($dto);

        $this->assertEquals(1.5, $rate->spread_pct);
        $this->assertLessThan($rate->mid_rate, $rate->bid_rate);
        $this->assertGreaterThan($rate->mid_rate, $rate->ask_rate);
    }

    public function test_throws_on_invalid_pair(): void
    {
        $this->expectException(FxInvalidPairException::class);

        $dto = new CreateFxRateDto(
            baseCurrency: 'EUR',
            quoteCurrency: 'GBP',
            midRate: 1.1,
            rateType: 'market',
            source: 'Reuters',
        );

        $this->rates->create($dto);
    }

    public function test_can_get_active_rate(): void
    {
        $dto = new CreateFxRateDto(
            baseCurrency: 'USD',
            quoteCurrency: 'SYP',
            midRate: 13100,
            rateType: 'cbs_official',
            source: 'CBS',
        );
        $this->rates->create($dto);

        $rate = $this->rates->getActiveRate('USD', 'SYP');
        $this->assertNotNull($rate);
        $this->assertEquals(13100, $rate->mid_rate);
    }

    public function test_returns_null_when_no_active_rate(): void
    {
        $rate = $this->rates->getActiveRate('USD', 'SYP');
        $this->assertNull($rate);
    }

    /* ──── Quote Tests ──── */

    public function test_can_generate_quote(): void
    {
        $this->seedCbsRate();

        $dto = new GetQuoteDto(
            requestorId: 'user-001',
            requestorType: 'wallet',
            baseCurrency: 'USD',
            quoteCurrency: 'SYP',
            amount: 100,
        );

        $quote = $this->quotes->generate($dto);

        $this->assertInstanceOf(FxQuote::class, $quote);
        $this->assertEquals('active', $quote->status);
        $this->assertEquals(100, $quote->amount_in_base);
        $this->assertGreaterThan(0, $quote->amount_in_quote);
    }

    public function test_throws_on_below_minimum(): void
    {
        $this->seedCbsRate();

        $this->expectException(FxAmountBelowMinimumException::class);

        $dto = new GetQuoteDto(
            requestorId: 'user-001',
            requestorType: 'wallet',
            baseCurrency: 'USD',
            quoteCurrency: 'SYP',
            amount: 1,
        );

        $this->quotes->generate($dto);
    }

    public function test_throws_when_no_rate_available(): void
    {
        $this->expectException(FxRateUnavailableException::class);

        $dto = new GetQuoteDto(
            requestorId: 'user-001',
            requestorType: 'wallet',
            baseCurrency: 'USD',
            quoteCurrency: 'SYP',
            amount: 100,
        );

        $this->quotes->generate($dto);
    }

    /* ──── Conversion Tests ──── */

    public function test_can_execute_conversion(): void
    {
        $this->seedCbsRate();
        $quote = $this->quotes->generate(new GetQuoteDto(
            requestorId: 'user-001',
            requestorType: 'wallet',
            baseCurrency: 'USD',
            quoteCurrency: 'SYP',
            amount: 100,
        ));

        $dto = new ExecuteConversionDto(quoteId: $quote->id);
        $conversion = $this->conversions->execute($dto);

        $this->assertInstanceOf(FxConversion::class, $conversion);
        $this->assertEquals('completed', $conversion->status);
        $this->assertEquals(100, $conversion->from_amount);

        $this->assertNotNull($conversion->completed_at);

        $quote->refresh();
        $this->assertEquals('accepted', $quote->status);
    }

    public function test_throws_on_expired_quote(): void
    {
        $this->seedCbsRate();
        $quote = $this->quotes->generate(new GetQuoteDto(
            requestorId: 'user-001',
            requestorType: 'wallet',
            baseCurrency: 'USD',
            quoteCurrency: 'SYP',
            amount: 100,
            ttlSeconds: 1,
        ));

        $this->travel(2)->seconds();

        $this->expectException(FxRateExpiredException::class);
        $this->conversions->execute(new ExecuteConversionDto(quoteId: $quote->id));
    }

    public function test_throws_on_already_accepted_quote(): void
    {
        if (\DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Exception ordering requires MySQL');
        }

        $this->seedCbsRate();
        $quote = $this->quotes->generate(new GetQuoteDto(
            requestorId: 'user-001',
            requestorType: 'wallet',
            baseCurrency: 'USD',
            quoteCurrency: 'SYP',
            amount: 100,
        ));

        $this->conversions->execute(new ExecuteConversionDto(quoteId: $quote->id));

        $this->expectException(\Modules\FX\Exceptions\FxRateLockContentionException::class);
        $this->conversions->execute(new ExecuteConversionDto(quoteId: $quote->id));
    }

    public function test_invalid_pair_rejected(): void
    {
        $this->expectException(FxInvalidPairException::class);
        $this->rates->validatePair('EUR', 'GBP');
    }

    public function test_validate_rate_service(): void
    {
        $this->rates->validatePair('USD', 'SYP');
        $this->rates->validatePair('SYP', 'USD');
        $this->expectNotToPerformAssertions();
    }

    /* ──── Helpers ──── */

    private function seedCbsRate(): FxRate
    {
        return $this->rates->create(new CreateFxRateDto(
            baseCurrency: 'USD',
            quoteCurrency: 'SYP',
            midRate: 13100,
            rateType: 'cbs_official',
            source: 'CBS',
        ));
    }
}
