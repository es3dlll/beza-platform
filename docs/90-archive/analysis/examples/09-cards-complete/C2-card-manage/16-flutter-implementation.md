# 16 - تطبيق Flutter (Flutter Implementation) - إدارة البطاقة

```dart
// domain/repositories/i_card_manage_repository.dart
abstract class ICardManageRepository {
  Future<void> updateLimit(int cardId, double dailyLimit);
  Future<void> blockCard(int cardId);
  Future<void> unblockCard(int cardId);
  Future<void> reportLost(int cardId, {String? reason});
  Future<List<TransactionModel>> getTransactions(int cardId, {int page = 1});
}

// data/repositories/card_manage_repository.dart
class CardManageRepository implements ICardManageRepository {
  final http.Client client;
  final TokenService _tokenService;

  CardManageRepository(this.client, this._tokenService);

  Future<String> _getToken() async => (await _tokenService.getValidToken()) ?? '';

  @override
  Future<void> updateLimit(int cardId, double dailyLimit) async {
    final token = await _getToken();
    final response = await client.put(
      Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/limit'),
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'daily_limit': dailyLimit}),
    );
    if (response.statusCode != 200) throw Exception('فشل تحديث الحد');
  }

  @override
  Future<void> blockCard(int cardId) async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/block'),
      headers: {'Authorization': 'Bearer $token'},
    );
    if (response.statusCode != 200) throw Exception('فشل حظر البطاقة');
  }

  @override
  Future<void> unblockCard(int cardId) async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/unblock'),
      headers: {'Authorization': 'Bearer $token'},
    );
    if (response.statusCode != 200) throw Exception('فشل إلغاء الحظر');
  }

  @override
  Future<void> reportLost(int cardId, {String? reason}) async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/report-lost'),
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'reason': reason}),
    );
    if (response.statusCode != 200) throw Exception('فشل الإبلاغ');
  }

  @override
  Future<List<TransactionModel>> getTransactions(int cardId, {int page = 1}) async {
    final token = await _getToken();
    final response = await client.get(
      Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/transactions?page=$page'),
      headers: {'Authorization': 'Bearer $token'},
    );
    if (response.statusCode == 200) return (jsonDecode(response.body)['data'] as List).map((e) => TransactionModel.fromJson(e)).toList();
    throw Exception('فشل تحميل المعاملات');
  }
}

// presentation/bloc/card_manage_bloc.dart
abstract class CardManageEvent {}
class UpdateLimit extends CardManageEvent { final int cardId; final double limit; UpdateLimit(this.cardId, this.limit); }
class BlockCard extends CardManageEvent { final int cardId; BlockCard(this.cardId); }
class UnblockCard extends CardManageEvent { final int cardId; UnblockCard(this.cardId); }
class ReportLost extends CardManageEvent { final int cardId; final String? reason; ReportLost(this.cardId, {this.reason}); }

abstract class CardManageState {}
class CardManageInitial extends CardManageState {}
class CardManageLoading extends CardManageState {}
class CardManageSuccess extends CardManageState { final String message; CardManageSuccess(this.message); }
class CardManageFailure extends CardManageState { final String error; CardManageFailure(this.error); }

class CardManageBloc extends Bloc<CardManageEvent, CardManageState> {
  final ICardManageRepository repository;
  CardManageBloc({required this.repository}) : super(CardManageInitial()) {
    on<UpdateLimit>(_onUpdateLimit);
    on<BlockCard>(_onBlockCard);
    on<UnblockCard>(_onUnblockCard);
    on<ReportLost>(_onReportLost);
  }
  Future<void> _onUpdateLimit(UpdateLimit event, Emitter<CardManageState> emit) async {
    emit(CardManageLoading());
    try { await repository.updateLimit(event.cardId, event.limit); emit(CardManageSuccess('تم تحديث الحد')); }
    catch (e) { emit(CardManageFailure(e.toString())); }
  }
  Future<void> _onBlockCard(BlockCard event, Emitter<CardManageState> emit) async {
    emit(CardManageLoading());
    try { await repository.blockCard(event.cardId); emit(CardManageSuccess('تم حظر البطاقة')); }
    catch (e) { emit(CardManageFailure(e.toString())); }
  }
  Future<void> _onUnblockCard(UnblockCard event, Emitter<CardManageState> emit) async {
    emit(CardManageLoading());
    try { await repository.unblockCard(event.cardId); emit(CardManageSuccess('تم إلغاء الحظر')); }
    catch (e) { emit(CardManageFailure(e.toString())); }
  }
  Future<void> _onReportLost(ReportLost event, Emitter<CardManageState> emit) async {
    emit(CardManageLoading());
    try { await repository.reportLost(event.cardId, reason: event.reason); emit(CardManageSuccess('تم الإبلاغ، سيتم إصدار بديل')); }
    catch (e) { emit(CardManageFailure(e.toString())); }
  }
}

// presentation/screens/card_detail_screen.dart
class CardDetailScreen extends StatelessWidget {
  final int cardId;
  CardDetailScreen({required this.cardId});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('إدارة البطاقة')),
      body: BlocProvider(
        create: (_) => CardManageBloc(repository: CardManageRepository(http.Client())),
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Column(children: [
            Card(child: ListTile(title: Text('**** **** **** 1234'), subtitle: Text('SYP • نشطة'))),
            SizedBox(height: 16),
            ListTile(title: Text('تحديث الحد اليومي'), trailing: Icon(Icons.edit), onTap: () => _showLimitDialog(context)),
            ListTile(title: Text('حظر البطاقة'), trailing: Icon(Icons.block), onTap: () => _confirmAction(context, 'حظر', () => context.read<CardManageBloc>().add(BlockCard(cardId)))),
            ListTile(title: Text('إلغاء الحظر'), trailing: Icon(Icons.lock_open), onTap: () => _confirmAction(context, 'إلغاء الحظر', () => context.read<CardManageBloc>().add(UnblockCard(cardId)))),
            ListTile(title: Text('الإبلاغ عن فقدان'), trailing: Icon(Icons.report), onTap: () => _confirmAction(context, 'الإبلاغ عن فقدان', () => context.read<CardManageBloc>().add(ReportLost(cardId)))),
          ]),
        ),
      ),
    );
  }
  void _showLimitDialog(BuildContext context) { /* show dialog with limit field */ }
  void _confirmAction(BuildContext context, String action, VoidCallback onConfirm) {
    showDialog(context: context, builder: (_) => AlertDialog(
      title: Text('تأكيد $action'), actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: Text('إلغاء')),
        TextButton(onPressed: () { onConfirm(); Navigator.pop(context); }, child: Text('تأكيد')),
      ],
    ));
  }
}
```
