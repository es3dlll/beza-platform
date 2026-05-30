import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';

class GovCollectionsService {
  final ApiClient _client;

  GovCollectionsService(this._client);

  Future<Map<String, dynamic>> getProviders() async {
    final response = await _client.get(ApiEndpoints.govCollectionsProviders);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> inquire({
    required String providerCode,
    required String referenceNumber,
  }) async {
    final response = await _client.post(ApiEndpoints.govCollectionsInquire, data: {
      'provider_code': providerCode,
      'reference_number': referenceNumber,
    });
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> pay({
    required String paymentId,
  }) async {
    final response = await _client.post(ApiEndpoints.govCollectionsPay(paymentId));
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getHistory() async {
    final response = await _client.get(ApiEndpoints.govCollectionsHistory);
    return response.data as Map<String, dynamic>;
  }
}
