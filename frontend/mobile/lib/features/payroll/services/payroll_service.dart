import '../../../core/api/api_client.dart';

class PayrollService {
  final ApiClient _client;

  PayrollService(this._client);

  Future<Map<String, dynamic>> register({
    required String companyName,
    required String companyNameAr,
    required String phone,
    required String governorate,
    required String city,
    String? commercialRegistration,
    String? taxNumber,
    String? email,
    String? address,
  }) async {
    final response = await _client.post(
      '/payroll/register',
      data: {
        'company_name': companyName,
        'company_name_ar': companyNameAr,
        'phone': phone,
        'governorate': governorate,
        'city': city,
        if (commercialRegistration != null)
          'commercial_registration': commercialRegistration,
        if (taxNumber != null) 'tax_number': taxNumber,
        if (email != null) 'email': email,
        if (address != null) 'address': address,
      },
    );
    return response.data;
  }

  Future<Map<String, dynamic>> getMy() async {
    final response = await _client.get('/payroll/my');
    return response.data;
  }

  Future<Map<String, dynamic>> createBatch({
    required String periodMonth,
    String? notes,
    required List<Map<String, dynamic>> employees,
  }) async {
    final response = await _client.post(
      '/payroll/batches',
      data: {
        'period_month': periodMonth,
        if (notes != null) 'notes': notes,
        'employees': employees,
      },
    );
    return response.data;
  }

  Future<Map<String, dynamic>> getBatches() async {
    final response = await _client.get('/payroll/batches');
    return response.data;
  }

  Future<Map<String, dynamic>> getBatch(String id) async {
    final response = await _client.get('/payroll/batches/$id');
    return response.data;
  }

  Future<Map<String, dynamic>> approveBatch(String id) async {
    final response = await _client.post('/payroll/batches/$id/approve');
    return response.data;
  }

  Future<Map<String, dynamic>> processBatch(String id) async {
    final response = await _client.post('/payroll/batches/$id/process');
    return response.data;
  }
}
