# 16 - تطبيق Flutter (Flutter Implementation) - سحب بنكي (Bank Withdrawal)

## هيكل الملفات

```
lib/features/t6_withdraw-bank/
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

class BankWithdrawalBloc extends Bloc<BankWithdrawalEvent, BankWithdrawalState> {
  final IBankWithdrawalRepository repository;

  BankWithdrawalBloc({required this.repository})
      : super(BankWithdrawalInitial()) {
    on<SubmitBankWithdrawal>(_onSubmit);
    on<ResetBankWithdrawal>((event, emit) => emit(BankWithdrawalInitial()));
  }

  Future<void> _onSubmit(
    SubmitBankWithdrawal event,
    Emitter<BankWithdrawalState> emit,
  ) async {
    emit(BankWithdrawalLoading());
    try {
      final result = await repository.process(
        amount: event.amount,
        currency: event.currency,
        pin: event.pin,
      );
      emit(BankWithdrawalSuccess(result));
    } catch (e) {
      emit(BankWithdrawalFailure(e.toString()));
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
class BankWithdrawalScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => BankWithdrawalBloc(
        repository: BankWithdrawalRepository(
          dataSource: BankWithdrawalRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      ),
      child: Scaffold(
        appBar: AppBar(title: const Text('سحب بنكي')),
        body: BlocListener<BankWithdrawalBloc, BankWithdrawalState>(
          listener: (context, state) {
            if (state is BankWithdrawalSuccess) {
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
            if (state is BankWithdrawalFailure) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(state.error)),
              );
            }
          },
          child: const BankWithdrawalForm(),
        ),
      ),
    );
  }
}
```
