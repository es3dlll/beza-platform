import '../../../core/api/api_client.dart';

class WalletService {
  final ApiClient _client;

  WalletService(this._client);

  Future<Map<String, dynamic>> getWallet(String id) async {
    final response = await _client.get('/wallets/$id');
    return response.data;
  }

  Future<Map<String, dynamic>> getTransactions(String walletId, {int page = 1}) async {
    final response = await _client.get(
      '/wallets/$walletId/transactions',
      queryParameters: {'page': page},
    );
    return response.data;
  }

  Future<Map<String, dynamic>> createWallet(String currency, String type) async {
    final response = await _client.post('/wallets', data: {
      'currency': currency,
      'type': type,
    });
    return response.data;
  }

  Future<Map<String, dynamic>> deposit({
    required String walletId,
    required int amount,
    required String currency,
    required String reference,
  }) async {
    final response = await _client.post('/wallets/$walletId/deposit', data: {
      'amount': amount,
      'currency': currency,
      'reference': reference,
    });
    return response.data;
  }

  Future<Map<String, dynamic>> withdraw({
    required String walletId,
    required int amount,
    required String currency,
    required String reference,
  }) async {
    final response = await _client.post('/wallets/$walletId/withdraw', data: {
      'amount': amount,
      'currency': currency,
      'reference': reference,
    });
    return response.data;
  }

  Future<Map<String, dynamic>> transfer({
    required String fromWalletId,
    required String toWalletId,
    required int amount,
    required String currency,
    required String reference,
  }) async {
    final response = await _client.post('/wallets/$fromWalletId/transfer', data: {
      'amount': amount,
      'currency': currency,
      'recipient_wallet_id': toWalletId,
      'reference': reference,
    });
    return response.data;
  }
}
