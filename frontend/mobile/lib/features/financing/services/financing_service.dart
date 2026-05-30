import '../../../core/api/api_client.dart';

class FinancingService {
  final ApiClient _client;

  FinancingService(this._client);

  Future<Map<String, dynamic>> getProducts() async {
    final response = await _client.get('/financing/products');
    return response.data;
  }

  Future<Map<String, dynamic>> apply({
    required String productId,
    required int amount,
    required int termDays,
    String? purpose,
  }) async {
    final response = await _client.post(
      '/financing/apply',
      data: {
        'product_id': productId,
        'amount': amount,
        'term_days': termDays,
        if (purpose != null) 'purpose': purpose,
      },
    );
    return response.data;
  }

  Future<Map<String, dynamic>> getMyLoans() async {
    final response = await _client.get('/financing/my-loans');
    return response.data;
  }

  Future<Map<String, dynamic>> repay(String id, {required int amount}) async {
    final response = await _client.post(
      '/financing/$id/repay',
      data: {'amount': amount},
    );
    return response.data;
  }
}
