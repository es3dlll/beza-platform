# 16 - تطبيق Flutter (Flutter Implementation) - الفوترة المتكررة (Merchant Recurring)

```dart
// presentation/bloc/subscription_bloc.dart
class SubscriptionBloc extends Bloc<SubscriptionEvent, SubscriptionState> {
  final ISubscriptionRepository repository;
  SubscriptionBloc({required this.repository}) : super(SubscriptionInitial()) {
    on<CreateSubscription>(_onCreate);
    on<LoadSubscriptions>(_onLoad);
  }
  Future<void> _onCreate(CreateSubscription event, Emitter<SubscriptionState> emit) async {
    emit(SubscriptionLoading());
    try { final sub = await repository.create(event.data); emit(SubscriptionCreated(sub)); }
    catch (e) { emit(SubscriptionError(e.toString())); }
  }
  Future<void> _onLoad(LoadSubscriptions event, Emitter<SubscriptionState> emit) async {
    emit(SubscriptionLoading());
    try { final subs = await repository.getAll(); emit(SubscriptionsLoaded(subs)); }
    catch (e) { emit(SubscriptionError(e.toString())); }
  }
}

// presentation/screens/subscription_screen.dart
class SubscriptionScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('الاشتراكات')),
      body: BlocBuilder<SubscriptionBloc, SubscriptionState>(
        builder: (context, state) {
          if (state is SubscriptionsLoaded) return ListView.builder(
            itemCount: state.subscriptions.length,
            itemBuilder: (_, i) => ListTile(
              title: Text('\${state.subscriptions[i].amount} \${state.subscriptions[i].currency}'),
              subtitle: Text(state.subscriptions[i].status),
            ),
          );
          return Center(child: CircularProgressIndicator());
        },
      ),
    );
  }
}
```
