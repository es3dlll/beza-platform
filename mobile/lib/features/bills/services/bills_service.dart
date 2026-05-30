import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';

class BillsService {
  final ApiClient _client;

  BillsService(this._client);

  Future<Map<String, dynamic>> getProviders() async {
    final response = await _client.get(ApiEndpoints.billsProviders);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> inquiry({
    required String billProviderId,
    required String accountNumber,
  }) async {
    final response = await _client.post(ApiEndpoints.billsInquiry, data: {
      'bill_provider_id': billProviderId,
      'account_number': accountNumber,
    });
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> pay({
    required String billPaymentId,
    int? amount,
  }) async {
    final response = await _client.post(ApiEndpoints.billsPay, data: {
      'bill_payment_id': billPaymentId,
      if (amount != null) 'amount': amount,
    });
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getHistory() async {
    final response = await _client.get(ApiEndpoints.billsHistory);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getBill(String id) async {
    final response = await _client.get(ApiEndpoints.bill(id));
    return response.data as Map<String, dynamic>;
  }
}
