# 16 - تطبيق Flutter (Flutter Implementation) - تحويل بين العملات (W3 Exchange)

## هيكل الملفات

```
lib/features/exchange/
├── data/
│   ├── models/
│   │   ├── exchange_request_model.dart
│   │   └── exchange_response_model.dart
│   ├── repositories/
│   │   └── exchange_repository.dart
│   └── datasources/
│       └── exchange_remote_datasource.dart
├── domain/
│   ├── entities/
│   │   └── exchange_entity.dart
│   └── repositories/
│       └── i_exchange_repository.dart
└── presentation/
    ├── bloc/
    │   ├── exchange_bloc.dart
    │   ├── exchange_event.dart
    │   └── exchange_state.dart
    ├── screens/
    │   └── exchange_screen.dart
    └── widgets/
        ├── exchange_form.dart
        ├── currency_selector.dart
        ├── amount_field.dart
        ├── rate_display.dart
        └── confirmation_dialog.dart
```

## طبقة المجال (Domain Layer)

```dart
// domain/entities/exchange_entity.dart
class ExchangeEntity {
  final int id;
  final String referenceNumber;
  final String fromCurrency;
  final String toCurrency;
  final double amount;
  final double convertedAmount;
  final double fee;
  final double rate;
  final double feePercentage;
  final double newSypBalance;
  final double newUsdBalance;
  final DateTime completedAt;

  ExchangeEntity({
    required this.id,
    required this.referenceNumber,
    required this.fromCurrency,
    required this.toCurrency,
    required this.amount,
    required this.convertedAmount,
    required this.fee,
    required this.rate,
    required this.feePercentage,
    required this.newSypBalance,
    required this.newUsdBalance,
    required this.completedAt,
  });
}
```

## طبقة البيانات (Data Layer)

```dart
// data/models/exchange_response_model.dart
class ExchangeResponseModel {
  final bool success;
  final String message;
  final ExchangeTransaction? transaction;
  final NewBalances? newBalances;

  ExchangeResponseModel.fromJson(Map<String, dynamic> json)
      : success = json['success'] as bool,
        message = json['message'] as String,
        transaction = json['data']?['transaction'] != null
            ? ExchangeTransaction.fromJson(json['data']['transaction'])
            : null,
        newBalances = json['data']?['new_balances'] != null
            ? NewBalances.fromJson(json['data']['new_balances'])
            : null;

  ExchangeEntity toEntity() => ExchangeEntity(
    id: transaction!.id,
    referenceNumber: transaction!.referenceNumber,
    fromCurrency: transaction!.fromCurrency,
    toCurrency: transaction!.toCurrency,
    amount: transaction!.amount,
    convertedAmount: transaction!.convertedAmount,
    fee: transaction!.fee,
    rate: transaction!.rate,
    feePercentage: transaction!.feePercentage,
    newSypBalance: newBalances!.syp,
    newUsdBalance: newBalances!.usd,
    completedAt: transaction!.completedAt,
  );
}

class ExchangeTransaction {
  final int id;
  final String referenceNumber;
  final String fromCurrency;
  final String toCurrency;
  final double amount;
  final double convertedAmount;
  final double fee;
  final double rate;
  final double feePercentage;
  final DateTime completedAt;

  ExchangeTransaction.fromJson(Map<String, dynamic> json)
      : id = json['id'] as int,
        referenceNumber = json['reference_number'] as String,
        fromCurrency = json['from_currency'] as String,
        toCurrency = json['to_currency'] as String,
        amount = (json['amount'] as num).toDouble(),
        convertedAmount = (json['converted_amount'] as num).toDouble(),
        fee = (json['fee'] as num).toDouble(),
        rate = (json['rate'] as num).toDouble(),
        feePercentage = (json['fee_percentage'] as num).toDouble(),
        completedAt = DateTime.parse(json['completed_at'] as String);
}

class NewBalances {
  final double syp;
  final double usd;

  NewBalances.fromJson(Map<String, dynamic> json)
      : syp = (json['syp'] as num).toDouble(),
        usd = (json['usd'] as num).toDouble();
}
```

## طبقة العرض (Presentation Layer)

```dart
// presentation/bloc/exchange_bloc.dart
class ExchangeBloc extends Bloc<ExchangeEvent, ExchangeState> {
  final IExchangeRepository repository;

  ExchangeBloc({required this.repository})
      : super(ExchangeInitial()) {
    on<SubmitExchange>(_onSubmitExchange);
    on<ResetExchange>((event, emit) => emit(ExchangeInitial()));
  }

  Future<void> _onSubmitExchange(
    SubmitExchange event,
    Emitter<ExchangeState> emit,
  ) async {
    emit(ExchangeLoading());
    try {
      final result = await repository.exchange(
        fromCurrency: event.fromCurrency,
        toCurrency: event.toCurrency,
        amount: event.amount,
      );
      emit(ExchangeSuccess(result));
    } on ExchangeApiException catch (e) {
      emit(ExchangeFailure(e.message));
    } catch (e) {
      emit(ExchangeFailure('حدث خطأ غير متوقع: $e'));
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
// presentation/screens/exchange_screen.dart
class ExchangeScreen extends StatelessWidget {
  const ExchangeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => ExchangeBloc(
        repository: ExchangeRepository(
          dataSource: ExchangeRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      ),
      child: Scaffold(
        appBar: AppBar(title: const Text('صرافة')),
        body: BlocListener<ExchangeBloc, ExchangeState>(
          listener: (context, state) {
            if (state is ExchangeSuccess) {
              showDialog(
                context: context,
                builder: (_) => AlertDialog(
                  title: const Text('تمت الصرافة'),
                  content: Text(
                    'تم تحويل ${state.exchange.amount} ${state.exchange.fromCurrency}'
                    ' → ${state.exchange.convertedAmount} ${state.exchange.toCurrency}'
                    '\nالرسوم: ${state.exchange.fee} ${state.exchange.fromCurrency}'
                    '\nالسعر: 1 ${state.exchange.toCurrency} = ${state.exchange.rate} ${state.exchange.fromCurrency}',
                  ),
                  actions: [
                    TextButton(
                      onPressed: () {
                        Navigator.pop(context);
                        context.read<ExchangeBloc>().add(ResetExchange());
                      },
                      child: const Text('حسناً'),
                    ),
                  ],
                ),
              );
            }
          },
          child: const ExchangeForm(),
        ),
      ),
    );
  }
}
```

```dart
// presentation/widgets/exchange_form.dart
class ExchangeForm extends StatefulWidget {
  @override
  State<ExchangeForm> createState() => _ExchangeFormState();
}

class _ExchangeFormState extends State<ExchangeForm> {
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  String _fromCurrency = 'SYP';
  String _toCurrency = 'USD';

  void _submit() {
    if (!_formKey.currentState!.validate()) return;

    context.read<ExchangeBloc>().add(SubmitExchange(
      fromCurrency: _fromCurrency,
      toCurrency: _toCurrency,
      amount: double.parse(_amountController.text),
    ));
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<ExchangeBloc, ExchangeState>(
      builder: (context, state) {
        return Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const Text('تحويل بين العملات', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 24),
              DropdownButtonFormField<String>(
                value: _fromCurrency,
                decoration: const InputDecoration(labelText: 'من'),
                items: const [
                  DropdownMenuItem(value: 'SYP', child: Text('SYP - ل.س')),
                  DropdownMenuItem(value: 'USD', child: Text('USD - $')),
                ],
                onChanged: (v) => setState(() => _fromCurrency = v!),
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                value: _toCurrency,
                decoration: const InputDecoration(labelText: 'إلى'),
                items: const [
                  DropdownMenuItem(value: 'SYP', child: Text('SYP - ل.س')),
                  DropdownMenuItem(value: 'USD', child: Text('USD - $')),
                ],
                onChanged: (v) => setState(() => _toCurrency = v!),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _amountController,
                decoration: InputDecoration(
                  labelText: 'المبلغ',
                  suffixText: _fromCurrency,
                ),
                keyboardType: TextInputType.number,
                validator: (v) {
                  if (v?.isEmpty ?? true) return 'المبلغ مطلوب';
                  if (double.tryParse(v!) == null) return 'مبلغ غير صحيح';
                  return null;
                },
              ),
              const SizedBox(height: 8),
              Text('الحد الأدنى: ${_fromCurrency == "SYP" ? "1,000" : "1"} $_fromCurrency',
                  style: const TextStyle(color: Colors.grey, fontSize: 12)),
              const SizedBox(height: 24),
              SizedBox(
                height: 48,
                child: ElevatedButton(
                  onPressed: state is ExchangeLoading ? null : _submit,
                  child: state is ExchangeLoading
                      ? const CircularProgressIndicator()
                      : const Text('تأكيد الصرافة'),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
```
