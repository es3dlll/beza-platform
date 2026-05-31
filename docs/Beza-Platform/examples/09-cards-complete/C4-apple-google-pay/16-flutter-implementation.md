# 16 - تطبيق Flutter (Flutter Implementation) - Apple Pay و Google Pay

```dart
// data/models/wallet_pay_model.dart
class WalletProvision {
  final String dpan; final String token; final DateTime expiry;
  WalletProvision({required this.dpan, required this.token, required this.expiry});
  factory WalletProvision.fromJson(Map<String, dynamic> json) => WalletProvision(
    dpan: json['dpan'], token: json['token'], expiry: DateTime.parse(json['expiry']),
  );
}

class ProvisionedDevice {
  final String deviceId; final String walletType; final DateTime provisionedAt; final String status;
  ProvisionedDevice({required this.deviceId, required this.walletType, required this.provisionedAt, required this.status});
  factory ProvisionedDevice.fromJson(Map<String, dynamic> json) => ProvisionedDevice(
    deviceId: json['device_id'], walletType: json['wallet_type'],
    provisionedAt: DateTime.parse(json['provisioned_at']), status: json['status'],
  );
}

// domain/repositories/i_wallet_pay_repository.dart
abstract class IWalletPayRepository {
  Future<WalletProvision> provisionApplePay(int cardId, {String? deviceId, String? deviceName});
  Future<WalletProvision> provisionGooglePay(int cardId, {String? deviceId, String? deviceName});
  Future<List<ProvisionedDevice>> getDevices(int cardId);
  Future<Map<String, dynamic>> charge(String dpan, double amount, int merchantId, {String? description});
}

// data/repositories/wallet_pay_repository.dart
class WalletPayRepository implements IWalletPayRepository {
  final http.Client client;
  final TokenService _tokenService;

  WalletPayRepository(this.client, this._tokenService);

  Future<String> _getToken() async => (await _tokenService.getValidToken()) ?? '';

  @override
  Future<WalletProvision> provisionApplePay(int cardId, {String? deviceId, String? deviceName}) async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/wallet/apple-pay/provision'),
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'device_id': deviceId, 'device_name': deviceName}),
    );
    if (response.statusCode == 201) return WalletProvision.fromJson(jsonDecode(response.body));
    throw Exception('فشل إضافة البطاقة إلى Apple Pay');
  }

  @override
  Future<WalletProvision> provisionGooglePay(int cardId, {String? deviceId, String? deviceName}) async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/wallet/google-pay/provision'),
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'device_id': deviceId, 'device_name': deviceName}),
    );
    if (response.statusCode == 201) return WalletProvision.fromJson(jsonDecode(response.body));
    throw Exception('فشل إضافة البطاقة إلى Google Pay');
  }

  @override
  Future<List<ProvisionedDevice>> getDevices(int cardId) async {
    final token = await _getToken();
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/cards/$cardId/wallet/devices'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return (jsonDecode(response.body)['data'] as List).map((e) => ProvisionedDevice.fromJson(e)).toList();
    throw Exception('فشل تحميل الأجهزة');
  }

  @override
  Future<Map<String, dynamic>> charge(String dpan, double amount, int merchantId, {String? description}) async {
    final response = await client.post(
      Uri.parse('https://api.beza.sy/api/v1/wallet-pay/charge'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'dpan': dpan, 'amount': amount, 'merchant_id': merchantId, 'description': description}),
    );
    if (response.statusCode == 200) return jsonDecode(response.body);
    throw Exception('فشلت عملية الدفع');
  }
}

// presentation/bloc/wallet_pay_bloc.dart
abstract class WalletPayEvent {}
class ProvisionApplePay extends WalletPayEvent { final int cardId; ProvisionApplePay(this.cardId); }
class ProvisionGooglePay extends WalletPayEvent { final int cardId; ProvisionGooglePay(this.cardId); }
class LoadDevices extends WalletPayEvent { final int cardId; LoadDevices(this.cardId); }
class MakeCharge extends WalletPayEvent { final String dpan; final double amount; final int merchantId; MakeCharge(this.dpan, this.amount, this.merchantId); }

abstract class WalletPayState {}
class WalletPayInitial extends WalletPayState {}
class WalletPayLoading extends WalletPayState {}
class WalletPayProvisioned extends WalletPayState { final WalletProvision provision; WalletPayProvisioned(this.provision); }
class WalletPayDevicesLoaded extends WalletPayState { final List<ProvisionedDevice> devices; WalletPayDevicesLoaded(this.devices); }
class WalletPayCharged extends WalletPayState { final Map<String, dynamic> result; WalletPayCharged(this.result); }
class WalletPayFailure extends WalletPayState { final String error; WalletPayFailure(this.error); }

class WalletPayBloc extends Bloc<WalletPayEvent, WalletPayState> {
  final IWalletPayRepository repository;
  WalletPayBloc({required this.repository}) : super(WalletPayInitial()) {
    on<ProvisionApplePay>(_onProvisionApple);
    on<ProvisionGooglePay>(_onProvisionGoogle);
    on<LoadDevices>(_onLoadDevices);
    on<MakeCharge>(_onCharge);
  }
  Future<void> _onProvisionApple(ProvisionApplePay event, Emitter<WalletPayState> emit) async {
    emit(WalletPayLoading());
    try { emit(WalletPayProvisioned(await repository.provisionApplePay(event.cardId))); }
    catch (e) { emit(WalletPayFailure(e.toString())); }
  }
  Future<void> _onProvisionGoogle(ProvisionGooglePay event, Emitter<WalletPayState> emit) async {
    emit(WalletPayLoading());
    try { emit(WalletPayProvisioned(await repository.provisionGooglePay(event.cardId))); }
    catch (e) { emit(WalletPayFailure(e.toString())); }
  }
  Future<void> _onLoadDevices(LoadDevices event, Emitter<WalletPayState> emit) async {
    emit(WalletPayLoading());
    try { emit(WalletPayDevicesLoaded(await repository.getDevices(event.cardId))); }
    catch (e) { emit(WalletPayFailure(e.toString())); }
  }
  Future<void> _onCharge(MakeCharge event, Emitter<WalletPayState> emit) async {
    emit(WalletPayLoading());
    try { emit(WalletPayCharged(await repository.charge(event.dpan, event.amount, event.merchantId))); }
    catch (e) { emit(WalletPayFailure(e.toString())); }
  }
}
```
