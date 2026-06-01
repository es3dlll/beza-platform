# 10 - مصانع بيانات الاختبار (Test Data Factories)

```dart
// test/helpers/factories.dart
import 'package:beza_mobile/data/models/user_model.dart';
import 'package:beza_mobile/data/models/transaction_model.dart';
import 'package:beza_mobile/data/models/wallet_model.dart';

class UserModelFactory {
  static Map<String, dynamic> get validJson => {
    'id': 1,
    'uuid': 'abc-123',
    'name': 'أحمد',
    'phone': '963900000001',
    'email': 'ahmed@beza.example',
    'status': 'active',
    'kyc_status': 'verified',
  };

  static UserModel create() => UserModel.fromJson(validJson);
}

class WalletModelFactory {
  static Map<String, dynamic> createUsdJson() => {
    'id': 1,
    'user_id': 1,
    'currency': 'USD',
    'balance': 1000.00,
    'frozen_balance': 0,
    'is_active': true,
  };

  static Map<String, dynamic> createSypJson() => {
    'id': 2,
    'user_id': 1,
    'currency': 'SYP',
    'balance': 1000000.00,
    'frozen_balance': 0,
    'is_active': true,
  };
}

class TransactionModelFactory {
  static Map<String, dynamic> createTransferJson() => {
    'id': 1,
    'reference_number': 'BZ260527143200A1B2C3',
    'type': 'transfer',
    'status': 'completed',
    'amount': 100.00,
    'currency': 'USD',
    'fee': 0,
    'description': 'مصروف',
    'sender': {'id': 1, 'name': 'أحمد', 'phone': '963900000001'},
    'receiver': {'id': 2, 'name': 'محمد', 'phone': '963900000002'},
    'created_at': '2026-05-27T14:32:00+03:00',
    'completed_at': '2026-05-27T14:32:00+03:00',
  };
}
```
