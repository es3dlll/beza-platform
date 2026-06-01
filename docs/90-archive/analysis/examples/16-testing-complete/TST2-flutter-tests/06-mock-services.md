# 06 - محاكاة الخدمات (Mock Services)

## توليد Mock باستخدام Mockito

```dart
// test/helpers/mocks.dart
import 'package:mockito/annotations.dart';
import 'package:beza_mobile/data/repositories/auth_repository.dart';
import 'package:beza_mobile/data/repositories/transfer_repository.dart';
import 'package:beza_mobile/data/repositories/wallet_repository.dart';
import 'package:beza_mobile/services/api_client.dart';

@GenerateMocks([
  AuthRepository,
  TransferRepository,
  WalletRepository,
  ApiClient,
])
void main() {}

// تشغيل: flutter pub run build_runner build
```

## Mock Data

```dart
// test/helpers/mock_data.dart
import 'package:beza_mobile/domain/entities/user.dart';
import 'package:beza_mobile/domain/entities/transaction.dart';
import 'package:beza_mobile/domain/entities/wallet.dart';

class MockData {
  static User get mockUser => User(
    id: 1,
    name: 'أحمد',
    phone: '963900000001',
    email: 'ahmed@beza.example',
  );

  static Wallet get mockWallet => Wallet(
    id: 1,
    userId: 1,
    currency: 'USD',
    balance: 1000.00,
  );

  static Transaction get mockTransaction => Transaction(
    id: 1,
    referenceNumber: 'BZ260527143200A1B2C3',
    amount: 100.00,
    currency: 'USD',
    type: 'transfer',
    status: 'completed',
    createdAt: DateTime.now(),
  );

  static List<Transaction> get mockTransactions => [
    mockTransaction,
    Transaction(
      id: 2,
      referenceNumber: 'BZ260527143200D4E5F6',
      amount: 50.00,
      currency: 'USD',
      type: 'transfer',
      status: 'completed',
      createdAt: DateTime.now().subtract(Duration(hours: 1)),
    ),
  ];
}
```
