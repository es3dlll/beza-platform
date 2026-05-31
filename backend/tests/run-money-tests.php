<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Modules\Core\Enums\Currency;
use App\Modules\Core\Exceptions\CurrencyMismatchException;
use App\Modules\Core\Exceptions\InvalidAmountException;
use App\Modules\Core\ValueObjects\Money;

$passed = 0;
$failed = 0;

function test(string $name, callable $assert): void {
    global $passed, $failed;
    try {
        $assert();
        echo "  PASS: {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  FAIL: {$name} - {$e->getMessage()}\n";
        $failed++;
    }
}

echo "Money Unit Tests\n";
echo str_repeat('=', 60) . "\n";

// 1. Create from fils
test('can create money from fils', function () {
    $money = Money::fromFils(1500);
    assert($money->fils() === 1500, 'fils should be 1500');
    assert($money->currency() === Currency::SYP, 'currency should be SYP');
});

// 2. Create from SYP
test('can create money from SYP', function () {
    $money = Money::fromSYP(1.5);
    assert($money->fils() === 1500, '1.5 SYP should be 1500 fils');
});

// 3. Negative amount throws
test('throws for negative amount', function () {
    try {
        Money::fromFils(-100);
        assert(false, 'should have thrown');
    } catch (InvalidAmountException) {
        assert(true);
    }
});

// 4. Addition
test('can add two amounts', function () {
    $result = Money::fromFils(1000)->add(Money::fromFils(2000));
    assert($result->fils() === 3000, '1000 + 2000 = 3000');
});

// 5. Subtraction
test('can subtract amounts', function () {
    $result = Money::fromFils(5000)->subtract(Money::fromFils(2000));
    assert($result->fils() === 3000, '5000 - 2000 = 3000');
});

// 6. Subtraction resulting in negative throws
test('throws on subtract resulting in negative', function () {
    try {
        Money::fromFils(1000)->subtract(Money::fromFils(2000));
        assert(false, 'should have thrown');
    } catch (InvalidAmountException) {
        assert(true);
    }
});

// 7. Currency mismatch throws
test('throws on currency mismatch', function () {
    try {
        Money::fromFils(1000, Currency::SYP)->add(Money::fromFils(1000, Currency::USD));
        assert(false, 'should have thrown');
    } catch (CurrencyMismatchException) {
        assert(true);
    }
});

// 8. Arabic format
test('formats in Arabic with SYP symbol', function () {
    $formatted = Money::fromFils(1500500)->format();
    assert(str_contains($formatted, 'ل.س'), 'should contain SYP symbol');
    assert(str_contains($formatted, '1,500.500'), 'should format correctly');
});

// 9. Comparison
test('can compare amounts', function () {
    $small = Money::fromFils(500);
    $large = Money::fromFils(1000);
    assert($large->isGreaterThan($small), '1000 > 500');
    assert($small->isLessThan($large), '500 < 1000');
    assert($small->equals(Money::fromFils(500)), '500 == 500');
});

// 10. Multiplication
test('can multiply', function () {
    $result = Money::fromFils(300)->multiply(3);
    assert($result->fils() === 900, '300 * 3 = 900');
});

echo str_repeat('=', 60) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
