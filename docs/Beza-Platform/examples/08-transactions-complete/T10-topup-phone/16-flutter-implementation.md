# 16 - تطبيق Flutter (Flutter Implementation) - شحن رصيد هاتف (Phone Topup)

## هيكل الملفات

```
lib/features/t10_topup-phone/
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

class PhoneTopupBloc extends Bloc<PhoneTopupEvent, PhoneTopupState> {
  final IPhoneTopupRepository repository;

  PhoneTopupBloc({required this.repository})
      : super(PhoneTopupInitial()) {
    on<SubmitPhoneTopup>(_onSubmit);
    on<ResetPhoneTopup>((event, emit) => emit(PhoneTopupInitial()));
  }

  Future<void> _onSubmit(
    SubmitPhoneTopup event,
    Emitter<PhoneTopupState> emit,
  ) async {
    emit(PhoneTopupLoading());
    try {
      final result = await repository.process(
        amount: event.amount,
        currency: event.currency,
        pin: event.pin,
      );
      emit(PhoneTopupSuccess(result));
    } catch (e) {
      emit(PhoneTopupFailure(e.toString()));
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
class PhoneTopupScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => PhoneTopupBloc(
        repository: PhoneTopupRepository(
          dataSource: PhoneTopupRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      ),
      child: Scaffold(
        appBar: AppBar(title: const Text('شحن رصيد هاتف')),
        body: BlocListener<PhoneTopupBloc, PhoneTopupState>(
          listener: (context, state) {
            if (state is PhoneTopupSuccess) {
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
            if (state is PhoneTopupFailure) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(state.error)),
              );
            }
          },
          child: const PhoneTopupForm(),
        ),
      ),
    );
  }
}
```
