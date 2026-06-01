# 16 - تطبيق Flutter (Flutter Implementation) - سحب وكيل (Agent Withdrawal)

## هيكل الملفات

```
lib/features/t7_withdraw-agent/
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

class AgentWithdrawalBloc extends Bloc<AgentWithdrawalEvent, AgentWithdrawalState> {
  final IAgentWithdrawalRepository repository;

  AgentWithdrawalBloc({required this.repository})
      : super(AgentWithdrawalInitial()) {
    on<SubmitAgentWithdrawal>(_onSubmit);
    on<ResetAgentWithdrawal>((event, emit) => emit(AgentWithdrawalInitial()));
  }

  Future<void> _onSubmit(
    SubmitAgentWithdrawal event,
    Emitter<AgentWithdrawalState> emit,
  ) async {
    emit(AgentWithdrawalLoading());
    try {
      final result = await repository.process(
        amount: event.amount,
        currency: event.currency,
        pin: event.pin,
      );
      emit(AgentWithdrawalSuccess(result));
    } catch (e) {
      emit(AgentWithdrawalFailure(e.toString()));
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
class AgentWithdrawalScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => AgentWithdrawalBloc(
        repository: AgentWithdrawalRepository(
          dataSource: AgentWithdrawalRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      ),
      child: Scaffold(
        appBar: AppBar(title: const Text('سحب وكيل')),
        body: BlocListener<AgentWithdrawalBloc, AgentWithdrawalState>(
          listener: (context, state) {
            if (state is AgentWithdrawalSuccess) {
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
            if (state is AgentWithdrawalFailure) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(state.error)),
              );
            }
          },
          child: const AgentWithdrawalForm(),
        ),
      ),
    );
  }
}
```
