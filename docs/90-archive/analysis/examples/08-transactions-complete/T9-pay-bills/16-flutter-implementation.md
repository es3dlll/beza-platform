# 16 - تطبيق Flutter (Flutter Implementation) - دفع الفواتير (Pay Bills)

## هيكل الملفات

```
lib/features/t9_pay-bills/
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

class PayBillsBloc extends Bloc<PayBillsEvent, PayBillsState> {
  final IPayBillsRepository repository;

  PayBillsBloc({required this.repository})
      : super(PayBillsInitial()) {
    on<SubmitPayBills>(_onSubmit);
    on<ResetPayBills>((event, emit) => emit(PayBillsInitial()));
  }

  Future<void> _onSubmit(
    SubmitPayBills event,
    Emitter<PayBillsState> emit,
  ) async {
    emit(PayBillsLoading());
    try {
      final result = await repository.process(
        amount: event.amount,
        currency: event.currency,
        pin: event.pin,
      );
      emit(PayBillsSuccess(result));
    } catch (e) {
      emit(PayBillsFailure(e.toString()));
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
class PayBillsScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => PayBillsBloc(
        repository: PayBillsRepository(
          dataSource: PayBillsRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      ),
      child: Scaffold(
        appBar: AppBar(title: const Text('دفع الفواتير')),
        body: BlocListener<PayBillsBloc, PayBillsState>(
          listener: (context, state) {
            if (state is PayBillsSuccess) {
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
            if (state is PayBillsFailure) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(state.error)),
              );
            }
          },
          child: const PayBillsForm(),
        ),
      ),
    );
  }
}
```
