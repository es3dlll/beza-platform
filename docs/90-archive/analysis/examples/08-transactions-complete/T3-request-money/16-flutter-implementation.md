# 16 - تطبيق Flutter (Flutter Implementation) - طلب المال (Request Money)

## هيكل الملفات

```
lib/features/t3_request-money/
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

class RequestMoneyBloc extends Bloc<RequestMoneyEvent, RequestMoneyState> {
  final IRequestMoneyRepository repository;

  RequestMoneyBloc({required this.repository})
      : super(RequestMoneyInitial()) {
    on<SubmitRequestMoney>(_onSubmit);
    on<ResetRequestMoney>((event, emit) => emit(RequestMoneyInitial()));
  }

  Future<void> _onSubmit(
    SubmitRequestMoney event,
    Emitter<RequestMoneyState> emit,
  ) async {
    emit(RequestMoneyLoading());
    try {
      final result = await repository.process(
        amount: event.amount,
        currency: event.currency,
        pin: event.pin,
      );
      emit(RequestMoneySuccess(result));
    } catch (e) {
      emit(RequestMoneyFailure(e.toString()));
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
class RequestMoneyScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => RequestMoneyBloc(
        repository: RequestMoneyRepository(
          dataSource: RequestMoneyRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      ),
      child: Scaffold(
        appBar: AppBar(title: const Text('طلب المال')),
        body: BlocListener<RequestMoneyBloc, RequestMoneyState>(
          listener: (context, state) {
            if (state is RequestMoneySuccess) {
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
            if (state is RequestMoneyFailure) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(state.error)),
              );
            }
          },
          child: const RequestMoneyForm(),
        ),
      ),
    );
  }
}
```
