import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';

class AgentService {
  final ApiClient _client;

  AgentService(this._client);

  Future<Map<String, dynamic>> register(Map<String, dynamic> data) async {
    final response = await _client.post(ApiEndpoints.agentRegister, data: data);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getMyProfile() async {
    final response = await _client.get(ApiEndpoints.agentMy);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getTransactions() async {
    final response = await _client.get(ApiEndpoints.agentTransactions);
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getCommission(String id) async {
    final response = await _client.get(ApiEndpoints.agentCommission(id));
    return response.data as Map<String, dynamic>;
  }
}
