# 16 - تطبيق Flutter (Flutter Implementation) - عرض الرصيد (W2 Balance)

## هيكل الملفات

```
lib/features/home/
├── data/
│   ├── models/
│   │   └── balance_response_model.dart
│   ├── repositories/
│   │   └── balance_repository.dart
│   └── datasources/
│       └── balance_remote_datasource.dart
├── domain/
│   ├── entities/
│   │   └── balance_entity.dart
│   └── repositories/
│       └── i_balance_repository.dart
└── presentation/
    ├── bloc/
    │   ├── balance_bloc.dart
    │   ├── balance_event.dart
    │   └── balance_state.dart
    ├── screens/
    │   └── home_screen.dart
    └── widgets/
        ├── balance_card.dart
        └── wallet_info_card.dart
```

## طبقة المجال (Domain Layer)

```dart
// domain/entities/balance_entity.dart
class BalanceEntity {
  final WalletBalance syp;
  final WalletBalance usd;

  BalanceEntity({required this.syp, required this.usd});
}

class WalletBalance {
  final double balance;
  final double frozen;
  final double available;
  final String walletNumber;

  WalletBalance({
    required this.balance,
    required this.frozen,
    required this.available,
    required this.walletNumber,
  });
}
```

## طبقة البيانات (Data Layer)

```dart
// data/models/balance_response_model.dart
class BalanceResponseModel {
  final bool success;
  final WalletData? syp;
  final WalletData? usd;

  BalanceResponseModel.fromJson(Map<String, dynamic> json)
      : success = json['success'] as bool,
        syp = json['data']?['syp'] != null
            ? WalletData.fromJson(json['data']['syp'])
            : null,
        usd = json['data']?['usd'] != null
            ? WalletData.fromJson(json['data']['usd'])
            : null;

  BalanceEntity toEntity() => BalanceEntity(
    syp: WalletBalance(
      balance: syp!.balance,
      frozen: syp!.frozen,
      available: syp!.available,
      walletNumber: syp!.walletNumber,
    ),
    usd: WalletBalance(
      balance: usd!.balance,
      frozen: usd!.frozen,
      available: usd!.available,
      walletNumber: usd!.walletNumber,
    ),
  );
}

class WalletData {
  final double balance;
  final double frozen;
  final double available;
  final String walletNumber;

  WalletData.fromJson(Map<String, dynamic> json)
      : balance = (json['balance'] as num).toDouble(),
        frozen = (json['frozen'] as num).toDouble(),
        available = (json['available'] as num).toDouble(),
        walletNumber = json['wallet_number'] as String;
}
```

```dart
// data/datasources/balance_remote_datasource.dart
class BalanceRemoteDataSource {
  final http.Client client;
  final String baseUrl;

  BalanceRemoteDataSource({required this.baseUrl, required this.client});

  Future<Map<String, dynamic>> getBalance(String token) async {
    final response = await client.get(
      Uri.parse('$baseUrl/api/v1/wallet/balance'),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    final body = jsonDecode(response.body) as Map<String, dynamic>;

    if (response.statusCode == 200) {
      return body;
    } else {
      throw BalanceApiException(
        statusCode: response.statusCode,
        message: body['message'] as String? ?? 'فشل جلب الرصيد',
      );
    }
  }
}
```

```dart
// data/repositories/balance_repository.dart (تحديث)
class BalanceRepository implements IBalanceRepository {
  final BalanceRemoteDataSource dataSource;
  final TokenService _tokenService;

  BalanceRepository({required this.dataSource, required TokenService tokenService})
      : _tokenService = tokenService;

  Future<String> _getToken() async => (await _tokenService.getValidToken()) ?? '';

  @override
  Future<BalanceEntity> getBalance() async {
    final token = await _getToken();
    final response = await dataSource.getBalance(token);
    return BalanceResponseModel.fromJson(response).toEntity();
  }
}
```

## طبقة العرض (Presentation Layer) (BLoC)

```dart
// presentation/bloc/balance_bloc.dart
class BalanceBloc extends Bloc<BalanceEvent, BalanceState> {
  final IBalanceRepository repository;

  BalanceBloc({required this.repository})
      : super(BalanceInitial()) {
    on<FetchBalance>(_onFetchBalance);
    on<RefreshBalance>(_onRefreshBalance);
  }

  Future<void> _onFetchBalance(
    FetchBalance event,
    Emitter<BalanceState> emit,
  ) async {
    emit(BalanceLoading());
    try {
      final balance = await repository.getBalance();
      emit(BalanceLoaded(balance));
    } catch (e) {
      emit(BalanceFailure(e.toString()));
    }
  }

  Future<void> _onRefreshBalance(
    RefreshBalance event,
    Emitter<BalanceState> emit,
  ) async {
    try {
      final balance = await repository.getBalance();
      emit(BalanceLoaded(balance));
    } catch (e) {
      // keep current state on refresh failure
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
// presentation/screens/home_screen.dart
class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => BalanceBloc(
        repository: BalanceRepository(
          dataSource: BalanceRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      )..add(FetchBalance()),
      child: const HomeView(),
    );
  }
}

class HomeView extends StatelessWidget {
  const HomeView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Beza')),
      body: RefreshIndicator(
        onRefresh: () async {
          context.read<BalanceBloc>().add(RefreshBalance());
        },
        child: BlocBuilder<BalanceBloc, BalanceState>(
          builder: (context, state) {
            if (state is BalanceLoading) {
              return const Center(child: CircularProgressIndicator());
            }
            if (state is BalanceFailure) {
              return Center(child: Text('خطأ: ${state.error}'));
            }
            if (state is BalanceLoaded) {
              final b = state.balance;
              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  BalanceCard(
                    currency: 'SYP',
                    balance: b.syp.balance,
                    available: b.syp.available,
                    walletNumber: b.syp.walletNumber,
                  ),
                  const SizedBox(height: 12),
                  BalanceCard(
                    currency: 'USD',
                    balance: b.usd.balance,
                    available: b.usd.available,
                    walletNumber: b.usd.walletNumber,
                  ),
                ],
              );
            }
            return const SizedBox.shrink();
          },
        ),
      ),
    );
  }
}
```

```dart
// presentation/widgets/balance_card.dart
class BalanceCard extends StatelessWidget {
  final String currency;
  final double balance;
  final double available;
  final String walletNumber;

  const BalanceCard({
    super.key,
    required this.currency,
    required this.balance,
    required this.available,
    required this.walletNumber,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('محفظة $currency', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                Text(currency, style: const TextStyle(color: Colors.grey)),
              ],
            ),
            const SizedBox(height: 12),
            Text('${balance.toStringAsFixed(2)} $currency',
                style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            Text('المتاح: ${available.toStringAsFixed(2)} $currency',
                style: const TextStyle(color: Colors.green)),
            Text('رقم المحفظة: $walletNumber',
                style: const TextStyle(color: Colors.grey, fontSize: 12)),
          ],
        ),
      ),
    );
  }
}
```
