import '../../../core/api/api_client.dart';

class HomeData {
  final int balance;
  final String currency;
  final int unreadNotifications;
  final int? loyaltyPoints;

  HomeData({
    required this.balance,
    required this.currency,
    required this.unreadNotifications,
    this.loyaltyPoints,
  });
}

class HomeService {
  final ApiClient _client;

  HomeService(this._client);

  Future<HomeData> fetchHomeData() async {
    int balance = 0;
    String currency = 'SYP';
    int unreadCount = 0;
    int? points;

    try {
      final walletsRes = await _client.get('/wallets');
      final wallets = (walletsRes.data['data'] as List?) ?? [];
      if (wallets.isNotEmpty) {
        final w = wallets[0] as Map<String, dynamic>;
        balance = w['balance'] as int? ?? 0;
        currency = w['currency'] as String? ?? 'SYP';
      }
    } catch (_) {}

    try {
      final notifRes = await _client.get(
        '/notifications',
        queryParameters: {'per_page': 5},
      );
      final list = (notifRes.data['data'] as List?) ?? [];
      unreadCount = list.where((n) => (n as Map)['is_read'] == false).length;
    } catch (_) {}

    try {
      final pointsRes = await _client.get('/loyalty/points');
      final d = pointsRes.data['data'];
      if (d is Map<String, dynamic>) {
        points = d['points'] as int?;
      }
    } catch (_) {}

    return HomeData(
      balance: balance,
      currency: currency,
      unreadNotifications: unreadCount,
      loyaltyPoints: points,
    );
  }
}
