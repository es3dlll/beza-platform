import '../../../core/api/api_client.dart';

class FxService {
  final ApiClient _client;

  FxService(this._client);

  Future<Map<String, dynamic>> getRates() async {
    final response = await _client.get('/fx/rates');
    return response.data;
  }

  Future<Map<String, dynamic>> createQuote({
    required String baseCurrency,
    required String quoteCurrency,
    required int amount,
    String? rateType,
    int? ttlSeconds,
  }) async {
    final response = await _client.post(
      '/fx/quotes',
      data: {
        'base_currency': baseCurrency,
        'quote_currency': quoteCurrency,
        'amount': amount,
        if (rateType != null) 'rate_type': rateType,
        if (ttlSeconds != null) 'ttl_seconds': ttlSeconds,
      },
    );
    return response.data;
  }

  Future<Map<String, dynamic>> convert({required String quoteId}) async {
    final response = await _client.post(
      '/fx/conversions',
      data: {'quote_id': quoteId},
    );
    return response.data;
  }

  Future<Map<String, dynamic>> getQuotes() async {
    final response = await _client.get('/fx/quotes');
    return response.data;
  }
}
