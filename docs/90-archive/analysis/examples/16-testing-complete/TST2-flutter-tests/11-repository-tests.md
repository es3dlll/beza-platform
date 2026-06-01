# 11 - اختبارات الـ Repository (Repository Tests)

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:beza_mobile/data/repositories/transfer_repository.dart';

void main() {
  late TransferRepository repository;
  late MockApiClient mockApiClient;

  setUp(() {
    mockApiClient = MockApiClient();
    repository = TransferRepository(apiClient: mockApiClient);
  });

  group('TransferRepository', () {
    test('transfer returns success result', () async {
      when(mockApiClient.post('/transfer', any))
        .thenAnswer((_) async => {
          'success': true,
          'data': {
            'transaction': TransactionModelFactory.createTransferJson(),
            'new_balance': 900.00,
          },
        });

      final result = await repository.transfer(
        toPhone: '963900000002',
        amount: 100,
        currency: 'USD',
        pin: '1234',
      );

      expect(result.transaction.referenceNumber, startsWith('BZ'));
      expect(result.newBalance, 900.00);
    });

    test('transfer throws ApiException on error', () async {
      when(mockApiClient.post('/transfer', any))
        .thenThrow(ApiException(statusCode: 422, message: 'رصيد غير كافٍ'));

      expect(
        () => repository.transfer(
          toPhone: '963900000002',
          amount: 999999,
          currency: 'USD',
          pin: '1234',
        ),
        throwsA(isA<ApiException>()),
      );
    });
  });
}
```
