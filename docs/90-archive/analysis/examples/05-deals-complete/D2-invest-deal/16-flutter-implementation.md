# 16 - تطبيق Flutter (Flutter Implementation) - المشاركة في صفقة

## InvestDealBloc

```dart
abstract class InvestDealEvent {}
class SubmitInvestment extends InvestDealEvent {
  final int dealId;
  final double amount;
  SubmitInvestment({required this.dealId, required this.amount});
}

abstract class InvestDealState {}
class InvestInitial extends InvestDealState {}
class InvestLoading extends InvestDealState {}
class InvestSuccess extends InvestDealState {
  final InvestmentEntity investment;
  final double newBalance;
  InvestSuccess(this.investment, this.newBalance);
}
class InvestFailure extends InvestDealState {
  final String error;
  InvestFailure(this.error);
}

class InvestDealBloc extends Bloc<InvestDealEvent, InvestDealState> {
  final IInvestRepository repository;

  InvestDealBloc({required this.repository}) : super(InvestInitial()) {
    on<SubmitInvestment>(_onSubmit);
  }

  Future<void> _onSubmit(SubmitInvestment event, Emitter<InvestDealState> emit) async {
    emit(InvestLoading());
    try {
      final result = await repository.invest(event.dealId, event.amount);
      emit(InvestSuccess(result.investment, result.newBalance));
    } catch (e) {
      emit(InvestFailure(e.toString()));
    }
  }
}
```

## DealInvestScreen

```dart
class DealInvestScreen extends StatelessWidget {
  final DealEntity deal;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(deal.title)),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          children: [
            Text('المبلغ المتبقي: ${deal.remainingAmount} ${deal.currency}'),
            Text('نسبة الربح: ${deal.expectedProfitPercentage}%'),
            TextField(
              decoration: InputDecoration(labelText: 'المبلغ'),
              keyboardType: TextInputType.number,
            ),
            ElevatedButton(onPressed: () {}, child: Text('استثمر')),
          ],
        ),
      ),
    );
  }
}
```
