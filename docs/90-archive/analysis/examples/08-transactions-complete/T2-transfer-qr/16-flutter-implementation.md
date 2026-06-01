# 16 - تطبيق Flutter (Flutter Implementation) - التحويل عبر QR (QR Transfer)

## هيكل الملفات

```
lib/features/t2_transfer-qr/
├── data/
│   ├── models/
│   │   └── request_model.dart
│   │   └── response_model.dart
│   ├── repositories/
│   │   └── repository.dart
│   └── datasources/
│       └── remote_datasource.dart
├── domain/
│   ├── entities/
│   │   └── entity.dart
│   └── repositories/
│       └── i_repository.dart
└── presentation/
    ├── bloc/
    │   ├── bloc.dart
    │   ├── event.dart
    │   └── state.dart
    ├── screens/
    │   └── screen.dart
    └── widgets/
        └── form.dart
```

## BLoC Implementation

```dart
// presentation/bloc/bloc.dart
import 'package:flutter_bloc/flutter_bloc.dart';

class QRTransferBloc extends Bloc<QRTransferEvent, QRTransferState> {
  final IQRTransferRepository repository;

  QRTransferBloc({required this.repository})
      : super(QRTransferInitial()) {
    on<SubmitQRTransfer>(_onSubmit);
    on<ResetQRTransfer>((event, emit) => emit(QRTransferInitial()));
  }

  Future<void> _onSubmit(
    SubmitQRTransfer event,
    Emitter<QRTransferState> emit,
  ) async {
    emit(QRTransferLoading());
    try {
      final result = await repository.process(
        amount: event.amount,
        currency: event.currency,
        pin: event.pin,
      );
      emit(QRTransferSuccess(result));
    } catch (e) {
      emit(QRTransferFailure(e.toString()));
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
class QRTransferScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => QRTransferBloc(
        repository: QRTransferRepository(
          dataSource: QRTransferRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      ),
      child: Scaffold(
        appBar: AppBar(title: const Text('التحويل عبر QR')),
        body: BlocListener<QRTransferBloc, QRTransferState>(
          listener: (context, state) {
            if (state is QRTransferSuccess) {
              showDialog(
                context: context,
                builder: (_) => AlertDialog(
                  title: const Text('تم بنجاح'),
                  content: Text('تمت العملية بنجاح'),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text('حسناً'),
                    ),
                  ],
                ),
              );
            }
            if (state is QRTransferFailure) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(state.error)),
              );
            }
          },
          child: const QRTransferForm(),
        ),
      ),
    );
  }
}
```
