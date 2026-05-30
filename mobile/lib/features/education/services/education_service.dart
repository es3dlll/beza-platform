import '../../../core/api/api_client.dart';

class EducationService {
  final ApiClient _client;

  EducationService(this._client);

  Future<Map<String, dynamic>> getInstitutions() async {
    final response = await _client.get('/education/institutions');
    return response.data;
  }

  Future<Map<String, dynamic>> registerStudent({
    required String institutionId,
    required String studentId,
    required String fullName,
    required String fullNameAr,
    String? grade,
  }) async {
    final response = await _client.post(
      '/education/register-student',
      data: {
        'institution_id': institutionId,
        'student_id': studentId,
        'full_name': fullName,
        'full_name_ar': fullNameAr,
        if (grade != null) 'grade': grade,
      },
    );
    return response.data;
  }

  Future<Map<String, dynamic>> createFee({
    required String studentId,
    required String feeType,
    required int amount,
    required String dueDate,
  }) async {
    final response = await _client.post(
      '/education/create-fee',
      data: {
        'student_id': studentId,
        'fee_type': feeType,
        'amount': amount,
        'due_date': dueDate,
      },
    );
    return response.data;
  }

  Future<Map<String, dynamic>> payFee(String id, {required int amount}) async {
    final response = await _client.post(
      '/education/$id/pay-fee',
      data: {'amount': amount},
    );
    return response.data;
  }

  Future<Map<String, dynamic>> getStudentFees(String id) async {
    final response = await _client.get('/education/student/$id/fees');
    return response.data;
  }
}
