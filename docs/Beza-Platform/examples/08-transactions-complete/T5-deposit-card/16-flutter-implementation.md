# 16 - تطبيق Flutter (Flutter Implementation) - إيداع بطاقة ائتمانية (Card Deposit)

## هيكل الملفات

```
lib/features/t5_deposit-card/
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

class CardDepositBloc extends Bloc<CardDepositEvent, CardDepositState> {
  final ICardDepositRepository repository;

  CardDepositBloc({required this.repository})
      : super(CardDepositInitial()) {
    on<SubmitCardDeposit>(_onSubmit);
    on<ResetCardDeposit>((event, emit) => emit(CardDepositInitial()));
  }

  Future<void> _onSubmit(
    SubmitCardDeposit event,
    Emitter<CardDepositState> emit,
  ) async {
    emit(CardDepositLoading());
    try {
      final result = await repository.process(
        amount: event.amount,
        currency: event.currency,
        pin: event.pin,
      );
      emit(CardDepositSuccess(result));
    } catch (e) {
      emit(CardDepositFailure(e.toString()));
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
class CardDepositScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => CardDepositBloc(
        repository: CardDepositRepository(
          dataSource: CardDepositRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      ),
      child: Scaffold(
        appBar: AppBar(title: const Text('إيداع بطاقة ائتمانية')),
        body: BlocListener<CardDepositBloc, CardDepositState>(
          listener: (context, state) {
            if (state is CardDepositSuccess) {
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
            if (state is CardDepositFailure) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(state.error)),
              );
            }
          },
          child: const CardDepositForm(),
        ),
      ),
    );
  }
}
```
