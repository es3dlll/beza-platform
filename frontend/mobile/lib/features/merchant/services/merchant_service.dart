import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';

class MerchantService {
  final ApiClient _client;

  MerchantService(this._client);

  Future<Map<String, dynamic>> register(Map<String, dynamic> data) async {
    final response = await _client.post(ApiEndpoints.merchantRegister, data: data);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getMyProfile() async {
    final response = await _client.get(ApiEndpoints.merchantMy);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getStores() async {
    final response = await _client.get(ApiEndpoints.merchantStores);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> createStore(Map<String, dynamic> data) async {
    final response = await _client.post(ApiEndpoints.merchantStores, data: data);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> generateQr() async {
    final response = await _client.get(ApiEndpoints.merchantQrGenerate);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> pay(Map<String, dynamic> data) async {
    final response = await _client.post(ApiEndpoints.merchantPay, data: data);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getPayments() async {
    final response = await _client.get(ApiEndpoints.merchantPaymentsMy);
    return response.data as Map<String, dynamic>;
  }
}
