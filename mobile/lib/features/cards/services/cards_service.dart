import '../../../core/api/api_client.dart';
import '../models/card_model.dart';

class CardsService {
  final ApiClient _client;

  CardsService(this._client);

  Future<List<CardModel>> getCards() async {
    final response = await _client.get('/cards');
    final json = response.data as Map<String, dynamic>;
    if (json['success'] != true) {
      throw Exception(_extractError(json));
    }
    final dataList = json['data'] as List<dynamic>;
    return dataList
        .map((e) => CardModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<CardModel> getCard(String id) async {
    final response = await _client.get('/cards/$id');
    final json = response.data as Map<String, dynamic>;
    if (json['success'] != true) {
      throw Exception(_extractError(json));
    }
    return CardModel.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<CardModel> createCard({
    String? cardType,
    required String cardholderName,
    String? currency,
    bool? isVirtual,
  }) async {
    final response = await _client.post('/cards', data: {
      if (cardType != null) 'card_type': cardType,
      'cardholder_name': cardholderName,
      if (currency != null) 'currency': currency,
      if (isVirtual != null) 'is_virtual': isVirtual,
    });
    final json = response.data as Map<String, dynamic>;
    if (json['success'] != true) {
      throw Exception(_extractError(json));
    }
    return CardModel.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<CardModel> activateCard(String id) async {
    final response = await _client.post('/cards/$id/activate');
    final json = response.data as Map<String, dynamic>;
    if (json['success'] != true) {
      throw Exception(_extractError(json));
    }
    return CardModel.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<CardModel> suspendCard(String id, {String? reason}) async {
    final response = await _client.post('/cards/$id/suspend', data: {
      if (reason != null) 'reason': reason,
    });
    final json = response.data as Map<String, dynamic>;
    if (json['success'] != true) {
      throw Exception(_extractError(json));
    }
    return CardModel.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<CardModel> cancelCard(String id) async {
    final response = await _client.post('/cards/$id/cancel');
    final json = response.data as Map<String, dynamic>;
    if (json['success'] != true) {
      throw Exception(_extractError(json));
    }
    return CardModel.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<CardModel> updateLimits(
    String id, {
    int? daily,
    int? weekly,
    int? monthly,
    int? single,
  }) async {
    final response = await _client.put('/cards/$id/limits', data: {
      if (daily != null) 'daily_limit': daily,
      if (weekly != null) 'weekly_limit': weekly,
      if (monthly != null) 'monthly_limit': monthly,
      if (single != null) 'single_txn_limit': single,
    });
    final json = response.data as Map<String, dynamic>;
    if (json['success'] != true) {
      throw Exception(_extractError(json));
    }
    return CardModel.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<CardModel> updateSettings(
    String id, {
    bool? international,
    bool? atm,
    bool? contactless,
    bool? ecommerce,
  }) async {
    final response = await _client.put('/cards/$id/settings', data: {
      if (international != null) 'international_enabled': international,
      if (atm != null) 'atm_enabled': atm,
      if (contactless != null) 'contactless_enabled': contactless,
      if (ecommerce != null) 'ecommerce_enabled': ecommerce,
    });
    final json = response.data as Map<String, dynamic>;
    if (json['success'] != true) {
      throw Exception(_extractError(json));
    }
    return CardModel.fromJson(json['data'] as Map<String, dynamic>);
  }

  String _extractError(Map<String, dynamic> json) {
    final error = json['error'] as Map<String, dynamic>?;
    return (error?['message_ar'] as String?) ??
        (error?['message'] as String?) ??
        'حدث خطأ';
  }
}
