import '../../../core/api/api_client.dart';

class RemittanceService {
  final ApiClient _client;

  RemittanceService(this._client);

  Future<List<dynamic>> getCorridors() async {
    final response = await _client.get('/remittance/corridors');
    final body = response.data as Map<String, dynamic>;
    return (body['data'] as List<dynamic>?) ?? [];
  }

  Future<List<dynamic>> getBeneficiaries() async {
    final response = await _client.get('/remittance/beneficiaries');
    final body = response.data as Map<String, dynamic>;
    return (body['data'] as List<dynamic>?) ?? [];
  }

  Future<Map<String, dynamic>> createBeneficiary(Map<String, dynamic> data) async {
    final response = await _client.post('/remittance/beneficiaries', data: data);
    final body = response.data as Map<String, dynamic>;
    return (body['data'] as Map<String, dynamic>?) ?? body;
  }

  Future<Map<String, dynamic>> createOrder(Map<String, dynamic> data) async {
    final response = await _client.post('/remittance/orders', data: data);
    final body = response.data as Map<String, dynamic>;
    return (body['data'] as Map<String, dynamic>?) ?? body;
  }

  Future<List<dynamic>> getOrders() async {
    final response = await _client.get('/remittance/orders');
    final body = response.data as Map<String, dynamic>;
    return (body['data'] as List<dynamic>?) ?? [];
  }

  Future<Map<String, dynamic>> getOrder(String id) async {
    final response = await _client.get('/remittance/orders/$id');
    final body = response.data as Map<String, dynamic>;
    return (body['data'] as Map<String, dynamic>?) ?? body;
  }
}
