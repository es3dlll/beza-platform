import '../../../core/api/api_client.dart';

class LoyaltyService {
  final ApiClient _client;

  LoyaltyService(this._client);

  Future<Map<String, dynamic>> getPoints() async {
    final response = await _client.get('/loyalty/points');
    return response.data;
  }

  Future<Map<String, dynamic>> getPointsHistory() async {
    final response = await _client.get('/loyalty/points/history');
    return response.data;
  }

  Future<Map<String, dynamic>> getRewards() async {
    final response = await _client.get('/loyalty/rewards');
    return response.data;
  }

  Future<Map<String, dynamic>> redeem({required String rewardId}) async {
    final response = await _client.post(
      '/loyalty/points/redeem',
      data: {'reward_id': rewardId},
    );
    return response.data;
  }

  Future<Map<String, dynamic>> calculateCashback({
    required int transactionAmount,
    String? merchantCategory,
  }) async {
    final response = await _client.post(
      '/loyalty/cashback/calculate',
      data: {
        'transaction_amount': transactionAmount,
        if (merchantCategory != null) 'merchant_category': merchantCategory,
      },
    );
    return response.data;
  }

  Future<Map<String, dynamic>> getTiers() async {
    final response = await _client.get('/loyalty/tiers');
    return response.data;
  }
}
