# 16 - تطبيق Flutter (Flutter Implementation) - بوابة الدفع (Payment Gateway)

```dart
// presentation/bloc/payment_link_bloc.dart
class PaymentLinkBloc extends Bloc<PaymentLinkEvent, PaymentLinkState> {
  final IPaymentLinkRepository repository;
  PaymentLinkBloc({required this.repository}) : super(PaymentLinkInitial()) {
    on<CreatePaymentLink>(_onCreate);
  }
  Future<void> _onCreate(CreatePaymentLink event, Emitter<PaymentLinkState> emit) async {
    emit(PaymentLinkLoading());
    try { final link = await repository.create(amount: event.amount, currency: event.currency, expiryHours: event.expiryHours);
      emit(PaymentLinkSuccess(link)); }
    catch (e) { emit(PaymentLinkFailure(e.toString())); }
  }
}

// presentation/screens/payment_link_screen.dart
class PaymentLinkScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('رابط دفع')),
      body: Column(children: [
        TextFormField(decoration: InputDecoration(labelText: 'المبلغ'), keyboardType: TextInputType.number),
        DropdownButtonFormField(items: [DropdownMenuItem(value: 'USD', child: Text('USD')), DropdownMenuItem(value: 'SYP', child: Text('SYP'))], onChanged: (_) {}),
        ElevatedButton(onPressed: () {}, child: Text('إنشاء رابط')),
      ]),
    );
  }
}
```
