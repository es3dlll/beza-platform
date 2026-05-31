import 'dart:math';

enum Currency { syp, usd, eur, try_ }

class Money {
  final int _fils;
  final Currency currency;

  Money(this._fils, {this.currency = Currency.syp}) {
    if (_fils < 0) {
      throw ArgumentError('المبلغ لا يمكن أن يكون سالباً');
    }
  }

  factory Money.fromFils(int fils, {Currency currency = Currency.syp}) {
    return Money(fils, currency: currency);
  }

  factory Money.fromSYP(num amount, {Currency currency = Currency.syp}) {
    final fils = (amount * 1000).round();
    return Money(fils, currency: currency);
  }

  int get fils => _fils;

  double get syp => _fils / 1000;

  Money operator +(Money other) {
    _assertSameCurrency(other);
    return Money(_fils + other._fils, currency: currency);
  }

  Money operator -(Money other) {
    _assertSameCurrency(other);
    return Money(_fils - other._fils, currency: currency);
  }

  Money operator *(num multiplier) {
    final fils = (_fils * multiplier).round();
    return Money(fils, currency: currency);
  }

  bool operator >(Money other) {
    _assertSameCurrency(other);
    return _fils > other._fils;
  }

  bool operator <(Money other) {
    _assertSameCurrency(other);
    return _fils < other._fils;
  }

  @override
  bool operator ==(Object other) =>
      other is Money && _fils == other._fils && currency == other.currency;

  @override
  int get hashCode => Object.hash(_fils, currency);

  String format() {
    final value = syp.toStringAsFixed(3);
    switch (currency) {
      case Currency.syp:
        return '$value ل.س';
      case Currency.usd:
        return '\$$value';
      case Currency.eur:
        return '$value €';
      case Currency.try_:
        return '$value ₺';
    }
  }

  void _assertSameCurrency(Money other) {
    if (currency != other.currency) {
      throw ArgumentError(
        'عملة غير متطابقة: متوقع ${currency.name}، مستلم ${other.currency.name}',
      );
    }
  }
}
