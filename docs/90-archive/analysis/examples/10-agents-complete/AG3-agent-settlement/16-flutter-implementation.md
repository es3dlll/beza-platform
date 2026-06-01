# 16 - تطبيق Flutter (Flutter Implementation) - تسوية العمولات (Agent Settlement)

```dart
// data/models/settlement_model.dart
class SettlementModel {
  final int id; final int agentId; final double amount; final double fee; final double netAmount;
  final String status; final int bankAccountId; final DateTime? approvedAt; final DateTime? paidAt; final DateTime createdAt;
  SettlementModel({required this.id, required this.agentId, required this.amount, required this.fee, required this.netAmount, required this.status, required this.bankAccountId, this.approvedAt, this.paidAt, required this.createdAt});
  factory SettlementModel.fromJson(Map<String, dynamic> json) => SettlementModel(
    id: json['id'], agentId: json['agent_id'], amount: (json['amount'] as num).toDouble(),
    fee: (json['fee'] as num).toDouble(), netAmount: (json['net_amount'] as num).toDouble(),
    status: json['status'], bankAccountId: json['bank_account_id'],
    approvedAt: json['approved_at'] != null ? DateTime.parse(json['approved_at']) : null,
    paidAt: json['paid_at'] != null ? DateTime.parse(json['paid_at']) : null,
    createdAt: DateTime.parse(json['created_at']),
  );
}

// domain/repositories/i_settlement_repository.dart
abstract class ISettlementRepository {
  Future<SettlementModel> request(double amount, int bankAccountId, {String? notes});
  Future<List<SettlementModel>> getHistory({String? status});
  Future<void> cancel(int id);
  Future<void> approve(int id, {double? fee, String? notes});
  Future<void> reject(int id, {String? reason});
}

// data/repositories/settlement_repository.dart
class SettlementRepository implements ISettlementRepository {
  final http.Client client;
  final TokenService _tokenService;

  SettlementRepository(this.client, this._tokenService);

  Future<String> _getToken() async => (await _tokenService.getValidToken()) ?? '';

  @override
  Future<SettlementModel> request(double amount, int bankAccountId, {String? notes}) async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('https://api.beza.sy/api/v1/agent/settlements'),
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'amount': amount, 'bank_account_id': bankAccountId, 'notes': notes}),
    );
    if (response.statusCode == 201) return SettlementModel.fromJson(jsonDecode(response.body)['data']);
    throw Exception(jsonDecode(response.body)['message'] ?? 'فشل طلب التسوية');
  }

  @override
  Future<List<SettlementModel>> getHistory({String? status}) async {
    final token = await _getToken();
    final params = status != null ? '?status=$status' : '';
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/agent/settlements$params'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return (jsonDecode(response.body)['data'] as List).map((e) => SettlementModel.fromJson(e)).toList();
    throw Exception('فشل تحميل السجل');
  }

  @override
  Future<void> cancel(int id) async {
    final token = await _getToken();
    final response = await client.post(Uri.parse('https://api.beza.sy/api/v1/agent/settlements/$id/cancel'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode != 200) throw Exception('فشل الإلغاء');
  }

  @override
  Future<void> approve(int id, {double? fee, String? notes}) async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('https://api.beza.sy/api/v1/admin/agent-settlements/$id/approve'),
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'fee': fee, 'notes': notes}),
    );
    if (response.statusCode != 200) throw Exception('فشل الموافقة');
  }

  @override
  Future<void> reject(int id, {String? reason}) async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('https://api.beza.sy/api/v1/admin/agent-settlements/$id/reject'),
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'reason': reason}),
    );
    if (response.statusCode != 200) throw Exception('فشل الرفض');
  }
}

// presentation/bloc/settlement_bloc.dart
abstract class SettlementEvent {}
class RequestSettlement extends SettlementEvent { final double amount; final int bankAccountId; final String? notes; RequestSettlement(this.amount, this.bankAccountId, {this.notes}); }
class LoadSettlements extends SettlementEvent { final String? status; LoadSettlements({this.status}); }
class CancelSettlement extends SettlementEvent { final int id; CancelSettlement(this.id); }

abstract class SettlementState {}
class SettlementInitial extends SettlementState {}
class SettlementLoading extends SettlementState {}
class SettlementHistoryLoaded extends SettlementState { final List<SettlementModel> settlements; SettlementHistoryLoaded(this.settlements); }
class SettlementSuccess extends SettlementState { final String message; SettlementSuccess(this.message); }
class SettlementFailure extends SettlementState { final String error; SettlementFailure(this.error); }

class SettlementBloc extends Bloc<SettlementEvent, SettlementState> {
  final ISettlementRepository repository;
  SettlementBloc({required this.repository}) : super(SettlementInitial()) {
    on<RequestSettlement>(_onRequest);
    on<LoadSettlements>(_onLoad);
    on<CancelSettlement>(_onCancel);
  }
  Future<void> _onRequest(RequestSettlement event, Emitter<SettlementState> emit) async {
    emit(SettlementLoading());
    try {
      await repository.request(event.amount, event.bankAccountId, notes: event.notes);
      emit(SettlementSuccess('تم تقديم طلب التسوية'));
    } catch (e) { emit(SettlementFailure(e.toString())); }
  }
  Future<void> _onLoad(LoadSettlements event, Emitter<SettlementState> emit) async {
    emit(SettlementLoading());
    try { emit(SettlementHistoryLoaded(await repository.getHistory(status: event.status))); }
    catch (e) { emit(SettlementFailure(e.toString())); }
  }
  Future<void> _onCancel(CancelSettlement event, Emitter<SettlementState> emit) async {
    emit(SettlementLoading());
    try { await repository.cancel(event.id); emit(SettlementSuccess('تم الإلغاء')); }
    catch (e) { emit(SettlementFailure(e.toString())); }
  }
}
```
