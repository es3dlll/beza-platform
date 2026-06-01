# 16 - تطبيق Flutter - إتمام الصفقة + توزيع الأرباح

## نظرة عامة

شاشة إتمام الصفقة خاصة بلوحة تحكم Admin (ويب بشكل أساسي)، لكن تطبيق Flutter يعرض:
1. إشعارات الأرباح للمستثمرين
2. شاشة متابعة الصفقات النشطة والمكتملة
3. رسم بياني لتوزيع الأرباح
4. احتفالية بالربح (انيميشن)

## هيكل الملفات

```
lib/
├── features/
│   └── deals/
│       ├── bloc/
│       │   ├── deal_completion_bloc.dart
│       │   ├── deal_completion_event.dart
│       │   └── deal_completion_state.dart
│       ├── models/
│       │   ├── investment_model.dart
│       │   └── completion_data_model.dart
│       ├── repository/
│       │   └── deal_repository.dart
│       └── screens/
│           ├── deal_completion_screen.dart
│           └── widgets/
│               ├── profit_distribution_chart.dart
│               ├── investment_list_tile.dart
│               └── profit_celebration_animation.dart
```

## 1. نموذج البيانات

```dart
// models/completion_data_model.dart
class CompletionData {
  final String dealId;
  final String dealTitle;
  final double totalInvested;
  final double totalProfit;
  final double profitPercentage;
  final String currency;
  final List<Investment> investments;
  final DealStatus status;

  CompletionData({
    required this.dealId,
    required this.dealTitle,
    required this.totalInvested,
    required this.totalProfit,
    required this.profitPercentage,
    required this.currency,
    required this.investments,
    required this.status,
  });

  factory CompletionData.fromJson(Map<String, dynamic> json) {
    return CompletionData(
      dealId: json['deal_id'],
      dealTitle: json['deal_title'],
      totalInvested: double.parse(json['total_invested'].toString()),
      totalProfit: double.parse(json['total_profit'].toString()),
      profitPercentage: double.parse(json['profit_percentage'].toString()),
      currency: json['currency'],
      investments: (json['investments'] as List)
          .map((i) => Investment.fromJson(i))
          .toList(),
      status: DealStatus.values.firstWhere((s) => s.name == json['status']),
    );
  }
}

// models/investment_model.dart
class Investment {
  final String id;
  final String userName;
  final double amount;
  final double profit;
  final double totalReturn;
  final String walletStatus;

  Investment({
    required this.id,
    required this.userName,
    required this.amount,
    required this.profit,
    required this.totalReturn,
    required this.walletStatus,
  });

  factory Investment.fromJson(Map<String, dynamic> json) {
    return Investment(
      id: json['id'],
      userName: json['user_name'],
      amount: double.parse(json['amount'].toString()),
      profit: double.parse(json['profit'].toString()),
      totalReturn: double.parse(json['total_return'].toString()),
      walletStatus: json['wallet_status'] ?? 'active',
    );
  }
}
```

## 2. BLoC لإدارة حالة الإتمام

```dart
// bloc/deal_completion_event.dart
abstract class DealCompletionEvent extends Equatable {
  const DealCompletionEvent();
}

class LoadCompletionData extends DealCompletionEvent {
  final String dealId;
  const LoadCompletionData(this.dealId);
  List<Object?> get props => [dealId];
}

class CompleteDeal extends DealCompletionEvent {
  final String dealId;
  final double profitActual;
  const CompleteDeal(this.dealId, this.profitActual);
  List<Object?> get props => [dealId, profitActual];
}

// bloc/deal_completion_state.dart
abstract class DealCompletionState extends Equatable {
  const DealCompletionState();
}

class CompletionInitial extends DealCompletionState {
  List<Object?> get props => [];
}

class CompletionLoading extends DealCompletionState {
  List<Object?> get props => [];
}

class CompletionLoaded extends DealCompletionState {
  final CompletionData data;
  const CompletionLoaded(this.data);
  List<Object?> get props => [data];
}

class CompletionProcessing extends DealCompletionState {
  final CompletionData data;
  const CompletionProcessing(this.data);
  List<Object?> get props => [data];
}

class CompletionSuccess extends DealCompletionState {
  final String message;
  const CompletionSuccess(this.message);
  List<Object?> get props => [message];
}

class CompletionError extends DealCompletionState {
  final String error;
  const CompletionError(this.error);
  List<Object?> get props => [error];
}

// bloc/deal_completion_bloc.dart
class DealCompletionBloc extends Bloc<DealCompletionEvent, DealCompletionState> {
  final DealRepository _repository;

  DealCompletionBloc(this._repository) : super(CompletionInitial()) {
    on<LoadCompletionData>(_onLoadCompletionData);
    on<CompleteDeal>(_onCompleteDeal);
  }

  Future<void> _onLoadCompletionData(
    LoadCompletionData event,
    Emitter<DealCompletionState> emit,
  ) async {
    emit(CompletionLoading());
    try {
      final data = await _repository.getCompletionData(event.dealId);
      emit(CompletionLoaded(data));
    } catch (e) {
      emit(CompletionError('فشل تحميل بيانات الصفقة: ${e.toString()}'));
    }
  }

  Future<void> _onCompleteDeal(
    CompleteDeal event,
    Emitter<DealCompletionState> emit,
  ) async {
    if (state is CompletionLoaded) {
      emit(CompletionProcessing(state as CompletionLoaded));
    }
    try {
      await _repository.completeDeal(event.dealId, event.profitActual);
      emit(const CompletionSuccess('تم إتمام الصفقة وتوزيع الأرباح بنجاح'));
    } catch (e) {
      emit(CompletionError('فشل إتمام الصفقة: ${e.toString()}'));
    }
  }
}
```

## 3. Repository

```dart
// services/api_client.dart
class ApiClient {
  final Dio _dio;
  final TokenService _tokenService;

  ApiClient(this._tokenService) : _dio = Dio() {
    _dio.interceptors.add(AuthInterceptor(_tokenService, _dio));
  }

  Future<ApiResponse> get(String path) async {
    final response = await _dio.get(path);
    return ApiResponse(response.data);
  }

  Future<ApiResponse> post(String path, {Map<String, dynamic>? data}) async {
    final response = await _dio.post(path, data: data);
    return ApiResponse(response.data);
  }

  WebSocketStream socket(String path) {
    // إنشاء WebSocket مع التوكن
    return WebSocketStream('wss://api.beza.sy$path');
  }
}

// repository/deal_repository.dart
class DealRepository {
  final ApiClient _apiClient;

  DealRepository(this._apiClient);

  Future<CompletionData> getCompletionData(String dealId) async {
    final response = await _apiClient.get('/admin/deals/$dealId/completion-data');
    return CompletionData.fromJson(response.data);
  }

  Future<void> completeDeal(String dealId, double profitActual) async {
    await _apiClient.post('/admin/deals/$dealId/complete', data: {
      'profit_actual': profitActual,
    });
  }

  Stream<int> getRealTimeStatus(String dealId) {
    return _apiClient.socket('/deals/$dealId/status').stream
        .map((event) => event['progress'] as int);
  }
}
```

## 4. الشاشة الرئيسية

```dart
// screens/deal_completion_screen.dart
class DealCompletionScreen extends StatefulWidget {
  final String dealId;
  const DealCompletionScreen({super.key, required this.dealId});

  @override
  State<DealCompletionScreen> createState() => _DealCompletionScreenState();
}

class _DealCompletionScreenState extends State<DealCompletionScreen> {
  late final DealCompletionBloc _bloc;

  @override
  void initState() {
    _bloc = DealCompletionBloc(DealRepository(ApiClient()));
    _bloc.add(LoadCompletionData(widget.dealId));
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('إتمام الصفقة'),
        centerTitle: true,
      ),
      body: BlocConsumer<DealCompletionBloc, DealCompletionState>(
        bloc: _bloc,
        listener: (context, state) {
          if (state is CompletionSuccess) {
            showDialog(
              context: context,
              builder: (_) => AlertDialog(
                title: const Text('تم بنجاح'),
                content: Text(state.message),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('حسناً'),
                  ),
                ],
              ),
            );
          }
          if (state is CompletionError) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(state.error), backgroundColor: Colors.red),
            );
          }
        },
        builder: (context, state) {
          if (state is CompletionLoading || state is CompletionInitial) {
            return const Center(child: CircularProgressIndicator());
          }
          if (state is CompletionLoaded) {
            return _buildCompletionView(state.data);
          }
          if (state is CompletionProcessing) {
            return _buildProcessingView((state as CompletionProcessing).data);
          }
          return const Center(child: Text('حدث خطأ غير متوقع'));
        },
      ),
    );
  }

  Widget _buildCompletionView(CompletionData data) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // بطاقة ملخص الصفقة
          _buildSummaryCard(data),
          const SizedBox(height: 16),
          // رسم بياني لتوزيع الأرباح
          ProfitDistributionChart(data: data),
          const SizedBox(height: 16),
          // قائمة المستثمرين
          const Text('قائمة المستثمرين', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          ...data.investments.map((inv) => InvestmentListTile(investment: inv)),
          const SizedBox(height: 24),
          // حقل إدخال الربح الفعلي
          _buildProfitInput(data),
        ],
      ),
    );
  }

  Widget _buildSummaryCard(CompletionData data) {
    return Card(
      elevation: 4,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Text(data.dealTitle, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const Divider(),
            _buildSummaryRow('إجمالي الاستثمارات', '\$${data.totalInvested.toStringAsFixed(2)}'),
            _buildSummaryRow('إجمالي الربح', '\$${data.totalProfit.toStringAsFixed(2)}'),
            _buildSummaryRow('نسبة الربح', '${data.profitPercentage.toStringAsFixed(2)}%'),
            _buildSummaryRow('عدد المستثمرين', '${data.investments.length}'),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  Widget _buildProfitInput(CompletionData data) {
    final controller = TextEditingController(text: data.totalProfit.toString());
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('الربح الفعلي:', style: TextStyle(fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),
        TextField(
          controller: controller,
          keyboardType: TextInputType.number,
          decoration: InputDecoration(
            suffixText: data.currency,
            border: const OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 16),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: () {
              final profit = double.tryParse(controller.text) ?? 0;
              _bloc.add(CompleteDeal(data.dealId, profit));
            },
            icon: const Icon(Icons.check_circle),
            label: const Text('إتمام الصفقة وتوزيع الأرباح'),
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.all(16),
              backgroundColor: Colors.green,
              foregroundColor: Colors.white,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildProcessingView(CompletionData data) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const CircularProgressIndicator(),
          const SizedBox(height: 16),
          const Text('جاري إتمام الصفقة وتوزيع الأرباح...'),
          const SizedBox(height: 8),
          Text('${data.investments.length} مستثمر', style: const TextStyle(color: Colors.grey)),
        ],
      ),
    );
  }
}
```

## 5. رسم بياني لتوزيع الأرباح

```dart
// widgets/profit_distribution_chart.dart
class ProfitDistributionChart extends StatelessWidget {
  final CompletionData data;
  const ProfitDistributionChart({super.key, required this.data});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('توزيع الأرباح على المستثمرين',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            SizedBox(
              height: 200,
              child: BarChart(
                BarChartData(
                  alignment: BarChartAlignment.spaceAround,
                  maxY: data.investments
                      .map((e) => e.profit)
                      .reduce((a, b) => a > b ? a : b),
                  barGroups: data.investments.asMap().entries.map((entry) {
                    final i = entry.key;
                    final inv = entry.value;
                    return BarChartGroupData(
                      x: i,
                      barRods: [
                        BarChartRodData(
                          toY: inv.profit,
                          color: inv.profit >= 0 ? Colors.green : Colors.red,
                          width: 20,
                          borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
                        ),
                      ],
                    );
                  }).toList(),
                  titlesData: FlTitlesData(
                    bottomTitles: AxisTitles(
                      sideTitles: SideTitles(
                        showTitles: true,
                        getTitlesWidget: (value, meta) {
                          final index = value.toInt();
                          if (index < data.investments.length) {
                            return Text(
                              data.investments[index].userName.length > 5
                                  ? '${data.investments[index].userName.substring(0, 5)}..'
                                  : data.investments[index].userName,
                              style: const TextStyle(fontSize: 10),
                            );
                          }
                          return const Text('');
                        },
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
```

## 6. أنيميشن احتفالية بالربح

```dart
// widgets/profit_celebration_animation.dart
class ProfitCelebrationAnimation extends StatefulWidget {
  final double profitAmount;
  final String currency;
  const ProfitCelebrationAnimation({
    super.key,
    required this.profitAmount,
    required this.currency,
  });

  @override
  State<ProfitCelebrationAnimation> createState() =>
      _ProfitCelebrationAnimationState();
}

class _ProfitCelebrationAnimationState
    extends State<ProfitCelebrationAnimation>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scaleAnimation;
  late Animation<double> _opacityAnimation;

  @override
  void initState() {
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    );
    _scaleAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.elasticOut),
    );
    _opacityAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeIn),
    );
    _controller.forward();
    super.initState();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        return Opacity(
          opacity: _opacityAnimation.value,
          child: Transform.scale(
            scale: _scaleAnimation.value,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.emoji_events, size: 80, color: Colors.amber),
                const SizedBox(height: 16),
                Text(
                  'تهانينا!',
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: Colors.green,
                      ),
                ),
                const SizedBox(height: 8),
                Text(
                  'لقد ربحت',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                Text(
                  '${widget.profitAmount.toStringAsFixed(2)} ${widget.currency}',
                  style: Theme.of(context).textTheme.headlineLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: Colors.green,
                        fontSize: 40,
                      ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
```

## 7. إشعار الربح في Flutter

```dart
// إعداد FCM للاستماع لإشعارات الأرباح
class ProfitNotificationHandler {
  static void configure() {
    NotificationService().configure(
      onNotificationOpen: (notification) {
        if (notification.data?['type'] == 'profit_received') {
          final dealId = notification.data?['deal_id'];
          final profitAmount = double.parse(
            notification.data?['profit_amount'] ?? '0',
          );

          Get.to(() => ProfitCelebrationScreen(
                dealId: dealId,
                profitAmount: profitAmount,
              ));
        }
      },
    );
  }
}
```

## 8. تحديث الحالة في الوقت الفعلي

```dart
// استخدام StreamBuilder لعرض تقدم الإتمام
StreamBuilder<int>(
  stream: _repository.getRealTimeStatus(widget.dealId),
  builder: (context, snapshot) {
    if (!snapshot.hasData) return const SizedBox();
    final progress = snapshot.data!;
    return Column(
      children: [
        LinearProgressIndicator(value: progress / 100),
        const SizedBox(height: 8),
        Text('تم توزيع $progress% من الأرباح'),
      ],
    );
  },
);
```

## ملخص المكونات

| المكون | الوظيفة | الحالة |
|--------|---------|--------|
| DealCompletionBloc | إدارة حالة الإتمام | ✅ |
| CompletionData Model | نموذج بيانات الإتمام | ✅ |
| Investment Model | نموذج بيانات المستثمر | ✅ |
| DealRepository | استدعاءات API | ✅ |
| DealCompletionScreen | الشاشة الرئيسية | ✅ |
| ProfitDistributionChart | رسم بياني للأرباح | ✅ |
| InvestmentListTile | عنصر قائمة المستثمر | ✅ |
| ProfitCelebrationAnimation | أنيميشن الاحتفال | ✅ |
| Real-time Status | تحديث مباشر عبر WebSocket | ✅ |
