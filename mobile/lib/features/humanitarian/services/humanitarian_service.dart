import '../../../core/api/api_client.dart';

class HumanitarianService {
  final ApiClient _client;

  HumanitarianService(this._client);

  Future<Map<String, dynamic>> getOrganizations() async {
    final response = await _client.get('/humanitarian/organizations');
    return response.data;
  }

  Future<Map<String, dynamic>> getPrograms({String? orgId}) async {
    final response = await _client.get(
      '/humanitarian/programs',
      queryParameters: orgId != null ? {'org_id': orgId} : null,
    );
    return response.data;
  }

  Future<Map<String, dynamic>> disburse({
    required String programId,
    required String type,
    required int amount,
    String? beneficiaryId,
  }) async {
    final response = await _client.post(
      '/humanitarian/disburse',
      data: {
        'program_id': programId,
        'type': type,
        'amount': amount,
        if (beneficiaryId != null) 'beneficiary_id': beneficiaryId,
      },
    );
    return response.data;
  }

  Future<Map<String, dynamic>> getHistory() async {
    final response = await _client.get('/humanitarian/history');
    return response.data;
  }
}
