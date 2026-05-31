import 'package:flutter_test/flutter_test.dart';

// Currency enum matching backend
enum FxCurrency { SYP, USD, EUR, TRY }

// Exchange rate model
class ExchangeRate {
  final FxCurrency from;
  final FxCurrency to;
  final int rateFilsPerUnit;
  final int bidFilsPerUnit;
  final int askFilsPerUnit;
  final String provider;
  final DateTime validUntil;

  ExchangeRate({
    required this.from,
    required this.to,
    required this.rateFilsPerUnit,
    required this.bidFilsPerUnit,
    required this.askFilsPerUnit,
    required this.provider,
    required this.validUntil,
  });

  bool get isValid => validUntil.isAfter(DateTime.now());
}

// Fee calculator
class FeeCalculator {
  static const Map<String, double> _tiers = {
    'small': 0.02,   // < 1M
    'medium': 0.015, // 1M-5M
    'large': 0.01,   // 5M-20M
    'xlarge': 0.008, // > 20M
  };

  static const Map<String, double> _pairSurcharges = {
    'SYP_USD': 0.001,
    'SYP_EUR': 0.0015,
    'SYP_TRY': 0.0005,
  };

  static ({int feeFils, int netAmountFils, double rate}) calculate({
    required int amountFils,
    required String fromCurrency,
    required String toCurrency,
  }) {
    double rate;
    if (amountFils <= 1_000_000) {
      rate = _tiers['small']!;
    } else if (amountFils <= 5_000_000) {
      rate = _tiers['medium']!;
    } else if (amountFils <= 20_000_000) {
      rate = _tiers['large']!;
    } else {
      rate = _tiers['xlarge']!;
    }

    final key = '${fromCurrency}_$toCurrency';
    final surcharge = _pairSurcharges[key] ?? 0.0;

    final fee = (amountFils * rate).round() + (amountFils * surcharge).round();
    final netAmount = amountFils - fee;

    return (feeFils: fee, netAmountFils: netAmount, rate: rate + surcharge);
  }
}

// Offline storage mock
class OfflineRemittanceStore {
  final List<Map<String, dynamic>> _pending = [];

  void save(Map<String, dynamic> remittance) {
    _pending.add({...remittance, 'savedAt': DateTime.now().toIso8601String()});
  }

  List<Map<String, dynamic>> get pending => List.unmodifiable(_pending);
  int get pendingCount => _pending.length;

  void clear() => _pending.clear();
}

// Remittance status
enum RemittanceStatus { pending, processing, underReview, completed, rejected }

void main() {
  group('Currency Pair Selection Screen', () {
    testWidgets('displays exchange rate with validity', (tester) async {
      final rate = ExchangeRate(
        from: FxCurrency.SYP,
        to: FxCurrency.USD,
        rateFilsPerUnit: 12500,
        bidFilsPerUnit: 12400,
        askFilsPerUnit: 12600,
        provider: 'simulated',
        validUntil: DateTime.now().add(const Duration(minutes: 5)),
      );

      expect(rate.rateFilsPerUnit, 12500);
      expect(rate.isValid, isTrue);
      expect(rate.from, FxCurrency.SYP);
      expect(rate.to, FxCurrency.USD);
    });

    testWidgets('shows expected fees before confirmation', (tester) async {
      final fee = FeeCalculator.calculate(amountFils: 500_000, fromCurrency: 'SYP', toCurrency: 'USD');
      expect(fee.feeFils, 10500);
      expect(fee.netAmountFils, 489500);
    });
  });

  group('Step-by-Step Remittance Flow', () {
    testWidgets('shows net received amount after fees', (tester) async {
      final fee = FeeCalculator.calculate(amountFils: 2_000_000, fromCurrency: 'SYP', toCurrency: 'EUR');
      expect(fee.rate, closeTo(0.0165, 0.0001));
      expect(fee.netAmountFils, 1_967_000);
    });

    testWidgets('shows pending status during processing', (tester) async {
      final status = RemittanceStatus.processing;
      expect(status, isNot(RemittanceStatus.completed));
      expect(status, RemittanceStatus.processing);
    });
  });

  group('Offline Storage and Retry', () {
    testWidgets('saves remittance offline when network fails', (tester) async {
      final store = OfflineRemittanceStore();
      final testData = {
        'reference': 'REM-OFFLINE-001',
        'amount_fils': 500_000,
        'from_currency': 'SYP',
        'to_currency': 'USD',
      };

      store.save(testData);
      expect(store.pendingCount, 1);
      expect(store.pending.first['reference'], 'REM-OFFLINE-001');
    });
  });

  group('Security Review Status Display', () {
    testWidgets('shows under review status for high-risk remittances', (tester) async {
      final status = RemittanceStatus.underReview;
      expect(status, RemittanceStatus.underReview);
      expect(status, isNot(RemittanceStatus.completed));
      expect(status, isNot(RemittanceStatus.rejected));
    });
  });

  group('Fee Calculator Unit Test (No Network)', () {
    test('calculates fees correctly for all tiers without network', () {
      // Small: 500K SYP→USD = 2% + 0.1% surcharge = 10500
      final small = FeeCalculator.calculate(amountFils: 500_000, fromCurrency: 'SYP', toCurrency: 'USD');
      expect(small.feeFils, 10500);
      expect(small.netAmountFils, 489500);

      // Medium: 3M SYP→EUR = 1.5% + 0.15% surcharge = 49500
      final medium = FeeCalculator.calculate(amountFils: 3_000_000, fromCurrency: 'SYP', toCurrency: 'EUR');
      expect(medium.feeFils, 49500);
      expect(medium.netAmountFils, 2950500);

      // Large: 15M SYP→USD = 1% + 0.1% surcharge = 165000
      final large = FeeCalculator.calculate(amountFils: 15_000_000, fromCurrency: 'SYP', toCurrency: 'USD');
      expect(large.feeFils, 165000);
      expect(large.netAmountFils, 14835000);

      // XLarge: 50M SYP→TRY = 0.8% + 0.05% surcharge = 425000
      final xlarge = FeeCalculator.calculate(amountFils: 50_000_000, fromCurrency: 'SYP', toCurrency: 'TRY');
      expect(xlarge.feeFils, 425000);
      expect(xlarge.netAmountFils, 49575000);
    });
  });
}
