# 16 - تطبيق Flutter (Flutter Implementation) - تسوية مدفوعات التاجر (Merchant Settlement)

```dart
// presentation/bloc/settlement_bloc.dart
class SettlementBloc extends Bloc<SettlementEvent, SettlementState> {
  final ISettlementRepository repository;
  SettlementBloc({required this.repository}) : super(SettlementInitial()) {
    on<RequestSettlement>(_onRequest);
    on<LoadSettlementHistory>(_onLoadHistory);
    on<CalculateSettlement>(_onCalculate);
  }
  Future<void> _onRequest(RequestSettlement event, Emitter<SettlementState> emit) async {
    emit(SettlementLoading());
    try { final result = await repository.request(currency: event.currency); emit(SettlementSuccess(result)); }
    catch (e) { emit(SettlementError(e.toString())); }
  }
}

// presentation/screens/settlement_screen.dart
class SettlementScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('التسوية البنكية')),
      body: Padding(padding: EdgeInsets.all(16), child: Column(children: [
        Card(child: Padding(padding: EdgeInsets.all(16), child: Column(children: [
          Text('المبلغ المتاح', style: TextStyle(fontSize: 16)),
          SizedBox(height: 8),
          Text('\$1,500.00', style: TextStyle(fontSize: 32, fontWeight: FontWeight.bold)),
          SizedBox(height: 16),
          ElevatedButton(onPressed: () {}, child: Text('طلب تسوية'), style: ElevatedButton.styleFrom(minimumSize: Size(double.infinity, 48))),
        ]))),
        SizedBox(height: 24),
        Text('آخر التسويات', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        // List of settlements
      ])),
    );
  }
}
```
