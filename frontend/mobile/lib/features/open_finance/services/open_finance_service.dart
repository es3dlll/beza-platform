import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';

class OpenFinanceService {
  final ApiClient _client;

  OpenFinanceService(this._client);

  Future<Map<String, dynamic>> registerApp({
    required String name,
    String? description,
    required List<String> redirectUris,
    required List<String> scopes,
  }) async {
    final response = await _client.post(ApiEndpoints.openFinanceRegisterApp, data: {
      'name': name,
      if (description != null) 'description': description,
      'redirect_uris': redirectUris,
      'scopes': scopes,
    });
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getApps() async {
    final response = await _client.get(ApiEndpoints.openFinanceApps);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getConsents() async {
    final response = await _client.get(ApiEndpoints.openFinanceConsents);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> createConsent({
    required String appId,
    required List<String> scopes,
  }) async {
    final response = await _client.post(ApiEndpoints.openFinanceCreateConsent, data: {
      'app_id': appId,
      'scopes': scopes,
    });
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> generateToken({
    required String appId,
    required String consentId,
  }) async {
    final response = await _client.post(ApiEndpoints.openFinanceGenerateToken, data: {
      'app_id': appId,
      'consent_id': consentId,
    });
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> revokeConsent(String id) async {
    final response = await _client.post(ApiEndpoints.openFinanceRevokeConsent(id));
    return response.data as Map<String, dynamic>;
  }
}
