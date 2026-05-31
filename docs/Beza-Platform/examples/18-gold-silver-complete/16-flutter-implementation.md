# 16 - تطبيق Flutter (Flutter Implementation) - فهرس - الذهب والفضة مدخرات (Gold & Silver Savings)

## هيكل الملفات

```
lib/features/gold_silver/
├── data/
│   ├── models/
│   │   ├── price_model.dart
│   │   ├── buy_request_model.dart
│   │   ├── buy_response_model.dart
│   │   ├── holding_model.dart
│   │   └── transaction_model.dart
│   ├── repositories/
│   │   └── gold_repository.dart
│   └── datasources/
│       └── gold_remote_datasource.dart
├── domain/
│   └── entities/
│       ├── price_entity.dart
│       ├── holding_entity.dart
│       └── transaction_entity.dart
└── presentation/
    ├── bloc/
    │   ├── gold_bloc.dart
    │   ├── gold_event.dart
    │   └── gold_state.dart
    ├── screens/
    │   └── gold_screen.dart
    └── widgets/
        ├── price_ticker_widget.dart
        ├── buy_form.dart
        ├── sell_form.dart
        └── portfolio_chart.dart
```

## طبقة المجال (Domain)

```dart
// domain/entities/price_entity.dart
class PriceEntity {
  final double priceUsd;
  final double bid;
  final double ask;
  final double change24h;
  final String timestamp;

  PriceEntity({
    required this.priceUsd,
    required this.bid,
    required this.ask,
    required this.change24h,
    required this.timestamp,
  });
}

// domain/entities/holding_entity.dart
class HoldingEntity {
  final int id;
  final String commodity;
  final double grams;
  final double avgPriceUsd;
  final double totalInvestedUsd;
  final double currentValueUsd;
  final double profitLoss;
  final double profitLossPercent;
  final String updatedAt;

  HoldingEntity({
    required this.id,
    required this.commodity,
    required this.grams,
    required this.avgPriceUsd,
    required this.totalInvestedUsd,
    required this.currentValueUsd,
    required this.profitLoss,
    required this.profitLossPercent,
    required this.updatedAt,
  });
}
```

## طبقة البيانات (Data)

```dart
// data/models/price_model.dart
class PriceModel {
  final double priceUsd;
  final double priceSyp;
  final double bid;
  final double ask;
  final double change24h;
  final String timestamp;

  PriceModel.fromJson(Map<String, dynamic> json)
      : priceUsd = (json['price_usd'] as num).toDouble(),
        priceSyp = (json['price_syp'] as num).toDouble(),
        bid = (json['bid'] as num).toDouble(),
        ask = (json['ask'] as num).toDouble(),
        change24h = (json['change_24h'] as num?)?.toDouble() ?? 0,
        timestamp = json['timestamp'] as String;

  PriceEntity toEntity() => PriceEntity(
    priceUsd: priceUsd, bid: bid, ask: ask,
    change24h: change24h, timestamp: timestamp,
  );
}

// data/models/holding_model.dart
class HoldingModel {
  final int id;
  final String commodity;
  final double grams;
  final double avgPriceUsd;
  final double totalInvestedUsd;
  final double currentValueUsd;
  final double profitLoss;
  final double profitLossPercent;
  final String updatedAt;

  HoldingModel.fromJson(Map<String, dynamic> json)
      : id = json['id'] as int,
        commodity = json['commodity'] as String,
        grams = (json['grams'] as num).toDouble(),
        avgPriceUsd = (json['avg_price_usd'] as num).toDouble(),
        totalInvestedUsd = (json['total_invested_usd'] as num).toDouble(),
        currentValueUsd = (json['current_value_usd'] as num).toDouble(),
        profitLoss = (json['profit_loss'] as num).toDouble(),
        profitLossPercent = (json['profit_loss_percent'] as num).toDouble(),
        updatedAt = json['updated_at'] as String;

  HoldingEntity toEntity() => HoldingEntity(
    id: id, commodity: commodity, grams: grams,
    avgPriceUsd: avgPriceUsd, totalInvestedUsd: totalInvestedUsd,
    currentValueUsd: currentValueUsd, profitLoss: profitLoss,
    profitLossPercent: profitLossPercent, updatedAt: updatedAt,
  );
}
```

## المصدر البعيد (Remote DataSource)

```dart
// data/datasources/gold_remote_datasource.dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class GoldRemoteDataSource {
  final http.Client client;
  final String baseUrl;

  GoldRemoteDataSource({required this.baseUrl, required this.client});

  Future<Map<String, dynamic>> getPrices(String token) async {
    final response = await client.get(
      Uri.parse('$baseUrl/api/v1/commodity/prices'),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );
    return _handleResponse(response);
  }

  Future<Map<String, dynamic>> buy({
    required Map<String, dynamic> body,
    required String token,
  }) async {
    final response = await client.post(
      Uri.parse('$baseUrl/api/v1/commodity/buy'),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode(body),
    );
    return _handleResponse(response);
  }

  Future<Map<String, dynamic>> sell({
    required Map<String, dynamic> body,
    required String token,
  }) async {
    final response = await client.post(
      Uri.parse('$baseUrl/api/v1/commodity/sell'),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode(body),
    );
    return _handleResponse(response);
  }

  Future<Map<String, dynamic>> getHoldings(String token) async {
    final response = await client.get(
      Uri.parse('$baseUrl/api/v1/commodity/holdings'),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );
    return _handleResponse(response);
  }

  Map<String, dynamic> _handleResponse(http.Response response) {
    final body = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    }
    throw GoldApiException(
      statusCode: response.statusCode,
      message: body['message'] as String? ?? 'فشلت العملية',
    );
  }
}

class GoldApiException implements Exception {
  final int statusCode;
  final String message;
  GoldApiException({required this.statusCode, required this.message});
  @override
  String toString() => message;
}
```

## الـ Repository

```dart
// data/repositories/gold_repository.dart
class GoldRepository {
  final GoldRemoteDataSource dataSource;

  GoldRepository({required this.dataSource});

  Future<Map<String, PriceEntity>> getPrices() async {
    final token = await _getToken();
    final response = await dataSource.getPrices(token);
    final data = response['data'] as Map<String, dynamic>;
    return {
      'gold': PriceModel.fromJson(data['gold']).toEntity(),
      'silver': PriceModel.fromJson(data['silver']).toEntity(),
    };
  }

  Future<Map<String, dynamic>> buy({
    required String commodity,
    required double amountSpent,
    required String currency,
  }) async {
    final token = await _getToken();
    return await dataSource.buy(
      body: {
        'commodity': commodity,
        'amount_spent': amountSpent,
        'currency': currency,
      },
      token: token,
    );
  }

  Future<Map<String, dynamic>> sell({
    required String commodity,
    required double grams,
    required String currency,
  }) async {
    final token = await _getToken();
    return await dataSource.sell(
      body: {
        'commodity': commodity,
        'grams': grams,
        'currency': currency,
      },
      token: token,
    );
  }

  Future<List<HoldingEntity>> getHoldings() async {
    final token = await _getToken();
    final response = await dataSource.getHoldings(token);
    final data = response['data'] as List;
    return data
        .map((e) => HoldingModel.fromJson(e).toEntity())
        .toList();
  }

  Future<String> _getToken() async {
    final tokenService = TokenService(FlutterSecureStorage());
    return (await tokenService.getValidToken()) ?? '';
  }
}
```

## طبقة BLoC

```dart
// presentation/bloc/gold_event.dart
abstract class GoldEvent {}

class LoadPrices extends GoldEvent {}

class BuyCommodity extends GoldEvent {
  final String commodity;
  final double amountSpent;
  final String currency;
  BuyCommodity({required this.commodity, required this.amountSpent, required this.currency});
}

class SellCommodity extends GoldEvent {
  final String commodity;
  final double grams;
  final String currency;
  SellCommodity({required this.commodity, required this.grams, required this.currency});
}

class LoadHoldings extends GoldEvent {}

class ResetOperation extends GoldEvent {}

// presentation/bloc/gold_state.dart
abstract class GoldState {}

class GoldInitial extends GoldState {}

class GoldLoading extends GoldState {}

class PricesLoaded extends GoldState {
  final Map<String, PriceEntity> prices;
  final bool marketOpen;
  PricesLoaded({required this.prices, required this.marketOpen});
}

class HoldingsLoaded extends GoldState {
  final List<HoldingEntity> holdings;
  HoldingsLoaded(this.holdings);
}

class BuySuccess extends GoldState {
  final double grams;
  final double totalSpent;
  final String commodityName;
  final HoldingEntity holding;
  final double newBalance;
  final String reference;
  BuySuccess({
    required this.grams, required this.totalSpent,
    required this.commodityName, required this.holding,
    required this.newBalance, required this.reference,
  });
}

class SellSuccess extends GoldState {
  final double grams;
  final double totalReceived;
  final String commodityName;
  final double newBalance;
  final String reference;
  SellSuccess({
    required this.grams, required this.totalReceived,
    required this.commodityName,
    required this.newBalance, required this.reference,
  });
}

class GoldError extends GoldState {
  final String message;
  GoldError(this.message);
}

// presentation/bloc/gold_bloc.dart
import 'package:flutter_bloc/flutter_bloc.dart';

class GoldBloc extends Bloc<GoldEvent, GoldState> {
  final GoldRepository repository;

  GoldBloc({required this.repository}) : super(GoldInitial()) {
    on<LoadPrices>(_onLoadPrices);
    on<BuyCommodity>(_onBuy);
    on<SellCommodity>(_onSell);
    on<LoadHoldings>(_onLoadHoldings);
    on<ResetOperation>((event, emit) => emit(GoldInitial()));
  }

  Future<void> _onLoadPrices(LoadPrices event, Emitter<GoldState> emit) async {
    emit(GoldLoading());
    try {
      final prices = await repository.getPrices();
      emit(PricesLoaded(prices: prices, marketOpen: true));
    } on GoldApiException catch (e) {
      emit(GoldError(e.message));
    } catch (e) {
      emit(GoldError('حدث خطأ: $e'));
    }
  }

  Future<void> _onBuy(BuyCommodity event, Emitter<GoldState> emit) async {
    emit(GoldLoading());
    try {
      final result = await repository.buy(
        commodity: event.commodity,
        amountSpent: event.amountSpent,
        currency: event.currency,
      );
      final data = result['data'];
      emit(BuySuccess(
        grams: (data['grams'] as num).toDouble(),
        totalSpent: (data['total_spent'] as num).toDouble(),
        commodityName: result['message'] as String,
        holding: HoldingModel.fromJson(data['holding']).toEntity(),
        newBalance: (data['new_balance'] as num).toDouble(),
        reference: data['reference'] as String,
      ));
    } on GoldApiException catch (e) {
      emit(GoldError(e.message));
    } catch (e) {
      emit(GoldError('حدث خطأ: $e'));
    }
  }

  Future<void> _onSell(SellCommodity event, Emitter<GoldState> emit) async {
    emit(GoldLoading());
    try {
      final result = await repository.sell(
        commodity: event.commodity,
        grams: event.grams,
        currency: event.currency,
      );
      final data = result['data'];
      emit(SellSuccess(
        grams: (data['grams'] as num).toDouble(),
        totalReceived: (data['total_received'] as num).toDouble(),
        commodityName: result['message'] as String,
        newBalance: (data['new_balance'] as num).toDouble(),
        reference: data['reference'] as String,
      ));
    } on GoldApiException catch (e) {
      emit(GoldError(e.message));
    } catch (e) {
      emit(GoldError('حدث خطأ: $e'));
    }
  }

  Future<void> _onLoadHoldings(LoadHoldings event, Emitter<GoldState> emit) async {
    emit(GoldLoading());
    try {
      final holdings = await repository.getHoldings();
      emit(HoldingsLoaded(holdings));
    } catch (e) {
      emit(GoldError('حدث خطأ: $e'));
    }
  }
}
```

## واجهة المستخدم (UI)

```dart
// presentation/screens/gold_screen.dart
class GoldScreen extends StatelessWidget {
  const GoldScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => GoldBloc(
        repository: GoldRepository(
          dataSource: GoldRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      )..add(LoadPrices()),
      child: const GoldView(),
    );
  }
}

class GoldView extends StatelessWidget {
  const GoldView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('الذهب والفضة')),
      body: BlocListener<GoldBloc, GoldState>(
        listener: (context, state) {
          if (state is BuySuccess || state is SellSuccess) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(state is BuySuccess
                  ? 'تم الشراء بنجاح!'
                  : 'تم البيع بنجاح!')),
            );
          }
          if (state is GoldError) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(state.message)),
            );
          }
        },
        child: BlocBuilder<GoldBloc, GoldState>(
          builder: (context, state) {
            if (state is GoldLoading) {
              return const Center(child: CircularProgressIndicator());
            }
            if (state is PricesLoaded) {
              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  const PriceTickerWidget(),
                  const SizedBox(height: 16),
                  DefaultTabController(
                    length: 2,
                    child: Column(
                      children: [
                        const TabBar(
                          tabs: [
                            Tab(text: 'شراء'),
                            Tab(text: 'بيع'),
                          ],
                        ),
                        SizedBox(
                          height: 400,
                          child: TabBarView(
                            children: [
                              const BuyForm(),
                              const SellForm(),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: () => context.read<GoldBloc>().add(LoadHoldings()),
                    icon: const Icon(Icons.account_balance_wallet),
                    label: const Text('محفظتي'),
                  ),
                ],
              );
            }
            return const Center(child: Text('اضغط على تحديث'));
          },
        ),
      ),
    );
  }
}

// presentation/widgets/price_ticker_widget.dart
class PriceTickerWidget extends StatelessWidget {
  const PriceTickerWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<GoldBloc, GoldState>(
      builder: (context, state) {
        if (state is! PricesLoaded) return const SizedBox();
        final gold = state.prices['gold']!;
        final silver = state.prices['silver']!;

        return Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                _priceRow('الذهب (XAU)', gold.ask, gold.change24h),
                const Divider(),
                _priceRow('الفضة (XAG)', silver.ask, silver.change24h),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _priceRow(String label, double price, double change) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.bold)),
        Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text('\$${price.toStringAsFixed(2)}',
                style: const TextStyle(fontSize: 18)),
            Text('${change >= 0 ? '+' : ''}${change.toStringAsFixed(2)}%',
                style: TextStyle(
                  color: change >= 0 ? Colors.green : Colors.red,
                )),
          ],
        ),
      ],
    );
  }
}

// presentation/widgets/buy_form.dart
class BuyForm extends StatefulWidget {
  const BuyForm({super.key});

  @override
  State<BuyForm> createState() => _BuyFormState();
}

class _BuyFormState extends State<BuyForm> {
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  String _commodity = 'gold';
  String _currency = 'USD';

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;
    context.read<GoldBloc>().add(BuyCommodity(
      commodity: _commodity,
      amountSpent: double.parse(_amountController.text),
      currency: _currency,
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: ListView(
        children: [
          DropdownButtonFormField<String>(
            value: _commodity,
            items: const [
              DropdownMenuItem(value: 'gold', child: Text('ذهب')),
              DropdownMenuItem(value: 'silver', child: Text('فضة')),
            ],
            onChanged: (v) => setState(() => _commodity = v!),
            decoration: const InputDecoration(labelText: 'السلعة'),
          ),
          TextFormField(
            controller: _amountController,
            decoration: InputDecoration(
              labelText: 'المبلغ المراد إنفاقه',
              suffixText: _currency,
            ),
            keyboardType: TextInputType.number,
            validator: (v) {
              if (v == null || v.isEmpty) return 'المبلغ مطلوب';
              if (double.tryParse(v) == null) return 'مبلغ غير صحيح';
              if (double.parse(v) < 1) return 'الحد الأدنى 1';
              return null;
            },
          ),
          DropdownButtonFormField<String>(
            value: _currency,
            items: const [
              DropdownMenuItem(value: 'USD', child: Text('USD')),
              DropdownMenuItem(value: 'SYP', child: Text('SYP')),
            ],
            onChanged: (v) => setState(() => _currency = v!),
            decoration: const InputDecoration(labelText: 'العملة'),
          ),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: _submit,
            child: const Text('شراء'),
          ),
        ],
      ),
    );
  }
}
```
