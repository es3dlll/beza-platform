# 08 - اختبارات الـ BLoC (BLoC Tests)

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:bloc_test/bloc_test.dart';
import 'package:mockito/mockito.dart';
import 'package:beza_mobile/features/transfer/presentation/bloc/transfer_bloc.dart';

void main() {
  late TransferBloc transferBloc;
  late MockTransferRepository mockRepository;

  setUp(() {
    mockRepository = MockTransferRepository();
    transferBloc = TransferBloc(transferRepository: mockRepository);
  });

  tearDown(() {
    transferBloc.close();
  });

  blocTest<TransferBloc, TransferState>(
    'emits [Loading, Success] when transfer succeeds',
    build: () {
      when(mockRepository.transfer(
        toPhone: anyNamed('toPhone'),
        amount: anyNamed('amount'),
        currency: anyNamed('currency'),
        pin: anyNamed('pin'),
      )).thenAnswer((_) async => TransferResult(
        transaction: MockData.mockTransaction,
        newBalance: 900.00,
      ));
      return transferBloc;
    },
    act: (bloc) => bloc.add(SubmitTransferEvent(
      toPhone: '963900000002',
      amount: 100,
      currency: 'USD',
      pin: '1234',
    )),
    expect: () => [
      isA<TransferLoading>(),
      isA<TransferSuccess>(),
    ],
  );

  blocTest<TransferBloc, TransferState>(
    'emits [Loading, Error] when transfer fails',
    build: () {
      when(mockRepository.transfer(
        toPhone: anyNamed('toPhone'),
        amount: anyNamed('amount'),
        currency: anyNamed('currency'),
        pin: anyNamed('pin'),
      )).thenThrow(ApiException(message: 'رصيد غير كافٍ'));
      return transferBloc;
    },
    act: (bloc) => bloc.add(SubmitTransferEvent(
      toPhone: '963900000002',
      amount: 999999,
      currency: 'USD',
      pin: '1234',
    )),
    expect: () => [
      isA<TransferLoading>(),
      isA<TransferError>(),
    ],
  );

  blocTest<TransferBloc, TransferState>(
    'validates input before submitting',
    build: () => transferBloc,
    act: (bloc) => bloc.add(SubmitTransferEvent(
      toPhone: '',
      amount: -1,
      currency: '',
      pin: '',
    )),
    expect: () => [
      isA<TransferValidationError>(),
    ],
  );
}
```
