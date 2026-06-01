# 16 - تطبيق Flutter (Flutter Implementation) - لوحة تحكم الوكيل (Agent Dashboard)

```dart
// data/models/dashboard_model.dart
class DashboardStats {
  final int totalTransactions; final double totalVolume;
  final double commissionEarned; final int todayCount; final double todayVolume; final double rating;
  DashboardStats({required this.totalTransactions, required this.totalVolume, required this.commissionEarned, required this.todayCount, required this.todayVolume, required this.rating});
  factory DashboardStats.fromJson(Map<String, dynamic> json) => DashboardStats(
    totalTransactions: json['total_transactions'], totalVolume: (json['total_volume'] as num).toDouble(),
    commissionEarned: (json['commission_earned'] as num).toDouble(), todayCount: json['today_count'],
    todayVolume: (json['today_volume'] as num).toDouble(), rating: (json['rating'] as num).toDouble(),
  );
}

class ActivityItem {
  final int id; final String type; final double amount; final String? customerName; final double commission; final DateTime createdAt;
  ActivityItem({required this.id, required this.type, required this.amount, this.customerName, required this.commission, required this.createdAt});
  factory ActivityItem.fromJson(Map<String, dynamic> json) => ActivityItem(
    id: json['id'], type: json['type'], amount: (json['amount'] as num).toDouble(),
    customerName: json['customer_name'], commission: (json['commission'] as num).toDouble(),
    createdAt: DateTime.parse(json['created_at']),
  );
}

// domain/repositories/i_dashboard_repository.dart
abstract class IDashboardRepository {
  Future<DashboardStats> getStats();
  Future<List<ActivityItem>> getActivities({int limit = 20});
  Future<Map<String, dynamic>> getDailyChart();
  Future<void> updateAvailability(bool available);
  Future<double> getCommissionSummary();
}

// data/repositories/dashboard_repository.dart
class DashboardRepository implements IDashboardRepository {
  final http.Client client;
  final TokenService _tokenService;

  DashboardRepository(this.client, this._tokenService);

  Future<String> _getToken() async => (await _tokenService.getValidToken()) ?? '';

  @override
  Future<DashboardStats> getStats() async {
    final token = await _getToken();
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/agent/dashboard'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return DashboardStats.fromJson(jsonDecode(response.body));
    throw Exception('فشل تحميل الإحصائيات');
  }
  @override
  Future<List<ActivityItem>> getActivities({int limit = 20}) async {
    final token = await _getToken();
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/agent/dashboard/activities?limit=$limit'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return (jsonDecode(response.body)['data'] as List).map((e) => ActivityItem.fromJson(e)).toList();
    throw Exception('فشل تحميل النشاطات');
  }
  @override
  Future<Map<String, dynamic>> getDailyChart() async {
    final token = await _getToken();
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/agent/dashboard/chart/daily'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return jsonDecode(response.body);
    throw Exception('فشل تحميل المخطط');
  }
  @override
  Future<void> updateAvailability(bool available) async {
    final token = await _getToken();
    final response = await client.put(Uri.parse('https://api.beza.sy/api/v1/agent/availability'), headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer $token'}, body: jsonEncode({'available': available}));
    if (response.statusCode != 200) throw Exception('فشل تحديث الحالة');
  }
  @override
  Future<double> getCommissionSummary() async {
    final token = await _getToken();
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/agent/commissions'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return (jsonDecode(response.body)['total_earned'] as num).toDouble();
    throw Exception('فشل تحميل العمولات');
  }
}

// presentation/bloc/dashboard_bloc.dart
abstract class DashboardEvent {}
class LoadDashboard extends DashboardEvent {}
class ToggleAvailability extends DashboardEvent { final bool available; ToggleAvailability(this.available); }

abstract class DashboardState {}
class DashboardInitial extends DashboardState {}
class DashboardLoading extends DashboardState {}
class DashboardLoaded extends DashboardState {
  final DashboardStats stats; final List<ActivityItem> activities; final Map<String, dynamic> chart; final double commissions;
  DashboardLoaded({required this.stats, required this.activities, required this.chart, required this.commissions});
}
class DashboardFailure extends DashboardState { final String error; DashboardFailure(this.error); }

class DashboardBloc extends Bloc<DashboardEvent, DashboardState> {
  final IDashboardRepository repository;
  DashboardBloc({required this.repository}) : super(DashboardInitial()) {
    on<LoadDashboard>(_onLoad);
    on<ToggleAvailability>(_onToggleAvailability);
  }
  Future<void> _onLoad(LoadDashboard event, Emitter<DashboardState> emit) async {
    emit(DashboardLoading());
    try {
      final stats = await repository.getStats();
      final activities = await repository.getActivities();
      final chart = await repository.getDailyChart();
      final commissions = await repository.getCommissionSummary();
      emit(DashboardLoaded(stats: stats, activities: activities, chart: chart, commissions: commissions));
    } catch (e) { emit(DashboardFailure(e.toString())); }
  }
  Future<void> _onToggleAvailability(ToggleAvailability event, Emitter<DashboardState> emit) async {
    try { await repository.updateAvailability(event.available); }
    catch (e) { emit(DashboardFailure(e.toString())); }
  }
}
```
