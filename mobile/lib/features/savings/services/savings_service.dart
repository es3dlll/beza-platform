import '../../../core/api/api_client.dart';

class SavingsService {
  final ApiClient _client;

  SavingsService(this._client);

  Future<Map<String, dynamic>> getGoals() async {
    final response = await _client.get('/savings/goals');
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getGoal(String id) async {
    final response = await _client.get('/savings/goals/$id');
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> createGoal({
    required String name,
    required String nameAr,
    required int targetAmount,
    required String targetDate,
    String? category,
    String? icon,
    String? color,
  }) async {
    final response = await _client.post('/savings/goals', data: {
      'name': name,
      'name_ar': nameAr,
      'target_amount': targetAmount,
      'target_date': targetDate,
      if (category != null) 'category': category,
      if (icon != null) 'icon': icon,
      if (color != null) 'color': color,
    });
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> contribute(String id, int amount) async {
    final response = await _client.post(
      '/savings/goals/$id/contribute',
      data: {'amount': amount},
    );
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> withdraw(
      String id, int amount, String? description) async {
    final response = await _client.post(
      '/savings/goals/$id/withdraw',
      data: {
        'amount': amount,
        if (description != null) 'description': description,
      },
    );
    return response.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getTransactions(String id) async {
    final response = await _client.get('/savings/goals/$id/transactions');
    return response.data as Map<String, dynamic>;
  }
}
