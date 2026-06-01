# 16 - تطبيق Flutter (Flutter Implementation) - إيداع وكيل (Agent Deposit)

## هيكل الملفات

```
lib/features/t8_deposit-agent/
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

class AgentDepositBloc extends Bloc<AgentDepositEvent, AgentDepositState> {
  final IAgentDepositRepository repository;

  AgentDepositBloc({required this.repository})
      : super(AgentDepositInitial()) {
    on<SubmitAgentDeposit>(_onSubmit);
    on<ResetAgentDeposit>((event, emit) => emit(AgentDepositInitial()));
  }

  Future<void> _onSubmit(
    SubmitAgentDeposit event,
    Emitter<AgentDepositState> emit,
  ) async {
    emit(AgentDepositLoading());
    try {
      final result = await repository.process(
        amount: event.amount,
        currency: event.currency,
        pin: event.pin,
      );
      emit(AgentDepositSuccess(result));
    } catch (e) {
      emit(AgentDepositFailure(e.toString()));
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
class AgentDepositScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => AgentDepositBloc(
        repository: AgentDepositRepository(
          dataSource: AgentDepositRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      ),
      child: Scaffold(
        appBar: AppBar(title: const Text('إيداع وكيل')),
        body: BlocListener<AgentDepositBloc, AgentDepositState>(
          listener: (context, state) {
            if (state is AgentDepositSuccess) {
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
            if (state is AgentDepositFailure) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(state.error)),
              );
            }
          },
          child: const AgentDepositForm(),
        ),
      ),
    );
  }
}
```
