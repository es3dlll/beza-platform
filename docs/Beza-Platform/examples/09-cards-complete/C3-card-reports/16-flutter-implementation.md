# 16 - تطبيق Flutter (Flutter Implementation) - تقارير البطاقة

```dart
// data/models/report_model.dart
class SpendingSummary {
  final double totalSpent; final int transactionCount; final String period; final double averagePerDay;
  SpendingSummary({required this.totalSpent, required this.transactionCount, required this.period, required this.averagePerDay});
  factory SpendingSummary.fromJson(Map<String, dynamic> json) => SpendingSummary(
    totalSpent: (json['total_spent'] as num).toDouble(), transactionCount: json['transaction_count'],
    period: json['period'], averagePerDay: (json['average_per_day'] as num).toDouble(),
  );
}

class MonthlyBreakdown {
  final String month; final double total; final int count;
  MonthlyBreakdown({required this.month, required this.total, required this.count});
  factory MonthlyBreakdown.fromJson(Map<String, dynamic> json) => MonthlyBreakdown(
    month: json['month'], total: (json['total'] as num).toDouble(), count: json['count'],
  );
}

// domain/repositories/i_card_report_repository.dart
abstract class ICardReportRepository {
  Future<SpendingSummary> getSummary(int cardId, {String? from, String? to});
  Future<List<MonthlyBreakdown>> getMonthlyBreakdown(int cardId);
  Future<List<CategorySpending>> getByCategory(int cardId);
  Future<String> export(int cardId, String format);
}

// data/repositories/card_report_repository.dart
class CardReportRepository implements ICardReportRepository {
  final http.Client client;
  final TokenService _tokenService;

  CardReportRepository(this.client, this._tokenService);

  Future<String> _getToken() async => (await _tokenService.getValidToken()) ?? '';

  @override
  Future<SpendingSummary> getSummary(int cardId, {String? from, String? to}) async {
    final token = await _getToken();
    final params = <String, String>{};
    if (from != null) params['from'] = from;
    if (to != null) params['to'] = to;
    final uri = Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/reports/summary').replace(queryParameters: params.isNotEmpty ? params : null);
    final response = await client.get(uri, headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return SpendingSummary.fromJson(jsonDecode(response.body));
    throw Exception('فشل تحميل التقرير');
  }

  @override
  Future<List<MonthlyBreakdown>> getMonthlyBreakdown(int cardId) async {
    final token = await _getToken();
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/reports/monthly'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return (jsonDecode(response.body)['data'] as List).map((e) => MonthlyBreakdown.fromJson(e)).toList();
    throw Exception('فشل تحميل التقرير الشهري');
  }

  @override
  Future<List<CategorySpending>> getByCategory(int cardId) async {
    final token = await _getToken();
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/reports/by-category'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return (jsonDecode(response.body)['data'] as List).map((e) => CategorySpending.fromJson(e)).toList();
    throw Exception('فشل تحميل التصنيفات');
  }

  @override
  Future<String> export(int cardId, String format) async {
    final token = await _getToken();
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/reports/export?format=$format'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return response.body;
    throw Exception('فشل تصدير التقرير');
  }
}

// presentation/bloc/card_report_bloc.dart
abstract class CardReportEvent {}
class LoadSummary extends CardReportEvent { final int cardId; final String? from; final String? to; LoadSummary(this.cardId, {this.from, this.to}); }
class LoadMonthly extends CardReportEvent { final int cardId; LoadMonthly(this.cardId); }
class LoadCategories extends CardReportEvent { final int cardId; LoadCategories(this.cardId); }

abstract class CardReportState {}
class CardReportInitial extends CardReportState {}
class CardReportLoading extends CardReportState {}
class CardReportSummaryLoaded extends CardReportState { final SpendingSummary summary; CardReportSummaryLoaded(this.summary); }
class CardReportMonthlyLoaded extends CardReportState { final List<MonthlyBreakdown> data; CardReportMonthlyLoaded(this.data); }
class CardReportCategoriesLoaded extends CardReportState { final List<CategorySpending> data; CardReportCategoriesLoaded(this.data); }
class CardReportFailure extends CardReportState { final String error; CardReportFailure(this.error); }

class CardReportBloc extends Bloc<CardReportEvent, CardReportState> {
  final ICardReportRepository repository;
  CardReportBloc({required this.repository}) : super(CardReportInitial()) {
    on<LoadSummary>(_onLoadSummary);
    on<LoadMonthly>(_onLoadMonthly);
    on<LoadCategories>(_onLoadCategories);
  }
  Future<void> _onLoadSummary(LoadSummary event, Emitter<CardReportState> emit) async {
    emit(CardReportLoading());
    try { emit(CardReportSummaryLoaded(await repository.getSummary(event.cardId, from: event.from, to: event.to))); }
    catch (e) { emit(CardReportFailure(e.toString())); }
  }
  Future<void> _onLoadMonthly(LoadMonthly event, Emitter<CardReportState> emit) async {
    emit(CardReportLoading());
    try { emit(CardReportMonthlyLoaded(await repository.getMonthlyBreakdown(event.cardId))); }
    catch (e) { emit(CardReportFailure(e.toString())); }
  }
  Future<void> _onLoadCategories(LoadCategories event, Emitter<CardReportState> emit) async {
    emit(CardReportLoading());
    try { emit(CardReportCategoriesLoaded(await repository.getByCategory(event.cardId))); }
    catch (e) { emit(CardReportFailure(e.toString())); }
  }
}

// presentation/screens/card_report_screen.dart
class CardReportScreen extends StatelessWidget {
  final int cardId;
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('تقارير البطاقة'), actions: [
        IconButton(icon: Icon(Icons.file_download), onPressed: () => _export(context)),
      ]),
      body: BlocProvider(
        create: (_) => CardReportBloc(repository: CardReportRepository(http.Client()))..add(LoadSummary(cardId)),
        child: DefaultTabController(
          length: 3,
          child: Column(children: [
            TabBar(tabs: [Tab(text: 'ملخص'), Tab(text: 'شهري'), Tab(text: 'فئات')]),
            Expanded(child: TabBarView(children: [
              _SummaryTab(cardId: cardId),
              _MonthlyTab(cardId: cardId),
              _CategoryTab(cardId: cardId),
            ])),
          ]),
        ),
      ),
    );
  }
  void _export(BuildContext context) async {
    try {
      await context.read<CardReportBloc>().repository.export(cardId, 'csv');
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('تم التصدير')));
    } catch (e) { ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('خطأ في التصدير'))); }
  }
}
```
