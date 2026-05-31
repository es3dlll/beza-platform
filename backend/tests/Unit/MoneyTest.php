<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Enums\Currency;
use App\Modules\Core\Exceptions\CurrencyMismatchException;
use App\Modules\Core\Exceptions\InvalidAmountException;
use App\Modules\Core\ValueObjects\Money;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    #[Test]
    public function it_can_create_money_from_fils(): void
    {
        $money = Money::fromFils(1500);

        $this->assertSame(1500, $money->fils());
        $this->assertSame(Currency::SYP, $money->currency());
    }

    #[Test]
    public function it_can_create_money_from_syp(): void
    {
        $money = Money::fromSYP(1.5);

        $this->assertSame(1500, $money->fils());
    }

    #[Test]
    public function it_throws_exception_for_negative_amount(): void
    {
        $this->expectException(InvalidAmountException::class);

        Money::fromFils(-100);
    }

    #[Test]
    public function it_can_add_two_amounts(): void
    {
        $a = Money::fromFils(1000);
        $b = Money::fromFils(2000);

        $result = $a->add($b);

        $this->assertSame(3000, $result->fils());
    }

    #[Test]
    public function it_can_subtract_amounts(): void
    {
        $a = Money::fromFils(5000);
        $b = Money::fromFils(2000);

        $result = $a->subtract($b);

        $this->assertSame(3000, $result->fils());
    }

    #[Test]
    public function it_throws_on_subtract_resulting_in_negative(): void
    {
        $this->expectException(InvalidAmountException::class);

        $a = Money::fromFils(1000);
        $b = Money::fromFils(2000);

        $a->subtract($b);
    }

    #[Test]
    public function it_throws_on_currency_mismatch(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        $syp = Money::fromFils(1000, Currency::SYP);
        $usd = Money::fromFils(1000, Currency::USD);

        $syp->add($usd);
    }

    #[Test]
    public function it_formats_in_arabic(): void
    {
        $money = Money::fromFils(1500500);

        $this->assertStringContainsString('ل.س', $money->format());
        $this->assertStringContainsString('1,500.500', $money->format());
    }

    #[Test]
    public function it_can_compare_amounts(): void
    {
        $small = Money::fromFils(500);
        $large = Money::fromFils(1000);

        $this->assertTrue($large->isGreaterThan($small));
        $this->assertTrue($small->isLessThan($large));
        $this->assertTrue($small->equals(Money::fromFils(500)));
    }

    #[Test]
    public function it_can_multiply(): void
    {
        $money = Money::fromFils(300);

        $result = $money->multiply(3);

        $this->assertSame(900, $result->fils());
    }
}
