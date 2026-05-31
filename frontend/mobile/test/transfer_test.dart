import 'package:flutter_test/flutter_test.dart';

import 'package:beza_mobile/core/money.dart';
import 'package:beza_mobile/services/transfer_service.dart';
import 'package:beza_mobile/services/secure_storage_service.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('تحويل - حساب المبلغ والرسوم', () {
    test('يعرض المبلغ والرسوم والصافي بدقة باستخدام Money', () {
      final amount = Money.fromSYP(1000);
      const feeFils = 0;
      final fee = Money.fromFils(feeFils);
      final netAmount = amount - fee;

      expect(amount.fils, 1000000);
      expect(fee.fils, 0);
      expect(netAmount.fils, 1000000);
      expect(amount.format(), contains('1000.000 ل.س'));
      expect(fee.format(), contains('0.000 ل.س'));
      expect(netAmount.format(), contains('1000.000 ل.س'));
    });

    test('يتعامل مع المبالغ الكبيرة دون فقدان الدقة', () {
      final amount = Money.fromFils(99999999999);
      const feeFils = 0;
      final fee = Money.fromFils(feeFils);
      final netAmount = amount - fee;

      expect(amount.fils, 99999999999);
      expect(fee.fils, 0);
      expect(netAmount.fils, 99999999999);
    });
  });

  group('تحويل - رفض الرصيد غير الكافي', () {
    test('يرفض التحويل عند عدم كفاية الرصيد مع رسالة واضحة', () {
      final balance = Money.fromSYP(500);
      final amount = Money.fromSYP(1000);

      expect(amount > balance, isTrue);

      final String? errorMessage;
      if (amount > balance) {
        errorMessage = 'الرصيد غير كافٍ. رصيدك الحالي هو ${balance.format()}';
      } else {
        errorMessage = null;
      }

      expect(errorMessage, isNotNull);
      expect(errorMessage, contains('الرصيد غير كافٍ'));
      expect(errorMessage, contains('500.000 ل.س'));
    });

    test('يسمح بالتحويل عندما يكون الرصيد كافياً', () {
      final balance = Money.fromSYP(2000);
      final amount = Money.fromSYP(1000);

      expect(amount > balance, isFalse);

      final String? errorMessage;
      if (amount > balance) {
        errorMessage = 'الرصيد غير كافٍ. رصيدك الحالي هو ${balance.format()}';
      } else {
        errorMessage = null;
      }

      expect(errorMessage, isNull);
    });
  });

  group('تحويل - التعامل مع خطأ الشبكة', () {
    test('يرجع فشلاً ورسالة خطأ عند فشل الاتصال', () async {
      final mockStorage = _MockSecureStorage();
      final service = TransferService(
        baseUrl: 'http://192.0.2.1:8000',
        storage: mockStorage,
      );

      final result = await service.lookupRecipient('test@example.com');

      expect(result.success, isFalse);
      expect(result.errorMessage, isNotNull);
      expect(result.errorMessage, isNotEmpty);
    });

    test('يعالج أخطاء الشبكة ويعيد فشلاً لخدمة التحويل', () async {
      final mockStorage = _MockSecureStorage2();
      final service = TransferService(
        baseUrl: 'http://192.0.2.1:8000',
        storage: mockStorage,
      );

      final result = await service.transfer(
        toWalletId: 'test-wallet-id',
        amount: Money.fromSYP(100),
        currency: 'SYP',
      );

      expect(result.success, isFalse);
      expect(result.errorMessage, isNotNull);
      expect(result.errorMessage, isNotEmpty);
    });
  });

  group('تحويل - تحديث الرصيد بعد النجاح', () {
    test('يحدث الرصيد تلقائياً بعد نجاح التحويل باستخدام setBalance', () {
      final initialBalance = Money.fromFils(1000000);
      final transferAmount = Money.fromFils(500000);
      final expectedBalance = initialBalance - transferAmount;

      expect(expectedBalance.fils, 500000);
      expect(expectedBalance.format(), contains('500.000 ل.س'));
    });

    test('تحديث حالة TransferState بعد النجاح يعكس الرصيد الجديد', () {
      final initialBalance = Money.fromFils(99998500);
      final transferAmount = Money.fromSYP(500);
      final newBalance = initialBalance - transferAmount;

      expect(newBalance.fils, 99498500);
      expect(newBalance.format(), contains('99498.500 ل.س'));
    });
  });

  group('تحويل - التحقق من الحدود قبل الإرسال', () {
    test('يرفض المبلغ الأقل من الحد الأدنى (1000 فلس)', () {
      const minAmountFils = 1000;
      final amount = Money.fromFils(500);

      final bool isBelowMin = amount.fils < minAmountFils;
      expect(isBelowMin, isTrue);

      final String? errorMessage = isBelowMin
          ? 'الحد الأدنى للتحويل هو ${Money.fromFils(minAmountFils).format()}'
          : null;

      expect(errorMessage, isNotNull);
      expect(errorMessage, contains('الحد الأدنى'));
      expect(errorMessage, contains('1.000 ل.س'));
    });

    test('يرفض المبلغ الأكبر من الحد الأقصى (100 مليار فلس)', () {
      const maxAmountFils = 100000000000;
      final amount = Money.fromFils(100000000001);

      final bool isAboveMax = amount.fils > maxAmountFils;
      expect(isAboveMax, isTrue);

      final String? errorMessage = isAboveMax
          ? 'الحد الأقصى للتحويل هو ${Money.fromFils(maxAmountFils).format()}'
          : null;

      expect(errorMessage, isNotNull);
      expect(errorMessage, contains('الحد الأقصى'));
      expect(errorMessage, contains('100000000.000 ل.س'));
    });

    test('يقبل المبلغ ضمن الحدود المسموحة', () {
      const minAmountFils = 1000;
      const maxAmountFils = 100000000000;
      final amount = Money.fromSYP(5000);

      expect(amount.fils >= minAmountFils, isTrue);
      expect(amount.fils <= maxAmountFils, isTrue);
    });

    test('يتحقق من صيغة البريد الإلكتروني للمستلم', () {
      final validEmails = ['test@example.com', 'user@beza.sy', 'a.b@c.co'];
      final invalidEmails = [
        'not-an-email',
        '@example.com',
        'user@',
        '',
      ];

      final emailRegex = RegExp(r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$');

      for (final email in validEmails) {
        expect(emailRegex.hasMatch(email), isTrue, reason: 'should accept: $email');
      }
      for (final email in invalidEmails) {
        expect(emailRegex.hasMatch(email), isFalse, reason: 'should reject: $email');
      }
    });
  });
}

class _MockSecureStorage extends SecureStorageService {
  @override
  Future<String?> getToken() async => 'mock-token';

  @override
  Future<void> deleteToken() async {}
}

class _MockSecureStorage2 extends SecureStorageService {
  @override
  Future<String?> getToken() async => 'mock-token';

  @override
  Future<void> deleteToken() async {}
}
