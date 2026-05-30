import '../../../core/api/api_client.dart';

class Transaction {
  final int id;
  final String walletId;
  final String type;
  final int amount;
  final String currency;
  final int balanceBefore;
  final int balanceAfter;
  final String? referenceType;
  final String? referenceId;
  final String status;
  final String? description;
  final String? relatedWalletId;
  final DateTime createdAt;

  Transaction({
    required this.id,
    required this.walletId,
    required this.type,
    required this.amount,
    required this.currency,
    required this.balanceBefore,
    required this.balanceAfter,
    this.referenceType,
    this.referenceId,
    required this.status,
    this.description,
    this.relatedWalletId,
    required this.createdAt,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) {
    return Transaction(
      id: json['id'] as int,
      walletId: json['wallet_id'] as String? ?? '',
      type: json['type'] as String? ?? '',
      amount: json['amount'] as int? ?? 0,
      currency: json['currency'] as String? ?? 'SYP',
      balanceBefore: json['balance_before'] as int? ?? 0,
      balanceAfter: json['balance_after'] as int? ?? 0,
      referenceType: json['reference_type'] as String?,
      referenceId: json['reference_id'] as String?,
      status: json['status'] as String? ?? 'completed',
      description: json['description'] as String?,
      relatedWalletId: json['related_wallet_id'] as String?,
      createdAt: DateTime.parse(json['created_at'] as String).toLocal(),
    );
  }
}

class TransactionService {
  final ApiClient _client;

  TransactionService(this._client);

  Future<List<Map<String, dynamic>>> getWallets() async {
    final response = await _client.get('/wallets');
    final data = response.data;
    return ((data['data'] as List?) ?? []).cast<Map<String, dynamic>>();
  }

  Future<Map<String, dynamic>> getTransactions(
    String walletId, {
    int page = 1,
    int perPage = 20,
    String? type,
  }) async {
    final params = <String, dynamic>{
      'page': page,
      'per_page': perPage,
    };
    if (type != null && type.isNotEmpty) params['type'] = type;

    final response = await _client.get(
      '/wallets/$walletId/transactions',
      queryParameters: params as Map<String, dynamic>?,
    );
    return response.data;
  }
}
