import 'dart:math';
import 'package:flutter_test/flutter_test.dart';
import 'package:beza_mobile/core/money.dart';

void main() {
  group('Money', () {
    test('can create from fils', () {
      final money = Money.fromFils(1500);
      expect(money.fils, 1500);
      expect(money.currency, Currency.syp);
    });

    test('can create from SYP', () {
      final money = Money.fromSYP(1.5);
      expect(money.fils, 1500);
    });

    test('throws for negative amount', () {
      expect(() => Money.fromFils(-100), throwsArgumentError);
    });

    test('can add two amounts', () {
      final result = Money.fromFils(1000) + Money.fromFils(2000);
      expect(result.fils, 3000);
    });

    test('can subtract amounts', () {
      final result = Money.fromFils(5000) - Money.fromFils(2000);
      expect(result.fils, 3000);
    });

    test('throws on subtract resulting in negative', () {
      expect(
        () => Money.fromFils(1000) - Money.fromFils(2000),
        throwsArgumentError,
      );
    });

    test('throws on currency mismatch', () {
      final syp = Money.fromFils(1000, currency: Currency.syp);
      final usd = Money.fromFils(1000, currency: Currency.usd);
      expect(() => syp + usd, throwsArgumentError);
    });

    test('formats with SYP symbol', () {
      final formatted = Money.fromFils(1500500).format();
      expect(formatted, contains('ل.س'));
    });

    test('can compare amounts', () {
      final small = Money.fromFils(500);
      final large = Money.fromFils(1000);
      expect(large > small, isTrue);
      expect(small < large, isTrue);
      expect(small == Money.fromFils(500), isTrue);
    });

    test('can multiply', () {
      final result = Money.fromFils(300) * 3;
      expect(result.fils, 900);
    });
  });
}
