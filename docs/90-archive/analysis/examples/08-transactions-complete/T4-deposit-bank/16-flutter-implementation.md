# 16 - تطبيق Flutter (Flutter Implementation) - إيداع بنكي (Bank Deposit)

## هيكل الملفات

```
lib/features/t4_deposit-bank/
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

class BankDepositBloc extends Bloc<BankDepositEvent, BankDepositState> {
  final IBankDepositRepository repository;

  BankDepositBloc({required this.repository})
      : super(BankDepositInitial()) {
    on<SubmitBankDeposit>(_onSubmit);
    on<ResetBankDeposit>((event, emit) => emit(BankDepositInitial()));
  }

  Future<void> _onSubmit(
    SubmitBankDeposit event,
    Emitter<BankDepositState> emit,
  ) async {
    emit(BankDepositLoading());
    try {
      final result = await repository.process(
        amount: event.amount,
        currency: event.currency,
        pin: event.pin,
      );
      emit(BankDepositSuccess(result));
    } catch (e) {
      emit(BankDepositFailure(e.toString()));
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
class BankDepositScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => BankDepositBloc(
        repository: BankDepositRepository(
          dataSource: BankDepositRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      ),
      child: Scaffold(
        appBar: AppBar(title: const Text('إيداع بنكي')),
        body: BlocListener<BankDepositBloc, BankDepositState>(
          listener: (context, state) {
            if (state is BankDepositSuccess) {
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
            if (state is BankDepositFailure) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(state.error)),
              );
            }
          },
          child: const BankDepositForm(),
        ),
      ),
    );
  }
}
```
