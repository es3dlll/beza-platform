import 'dart:convert';
import 'dart:io';
import '../core/money.dart';
import 'network_service.dart';
import 'secure_storage_service.dart';

class RecipientInfo {
  final String id;
  final String name;
  final String email;
  final String walletId;

  RecipientInfo({
    required this.id,
    required this.name,
    required this.email,
    required this.walletId,
  });
}

class TransferResult {
  final bool success;
  final String? transactionId;
  final Money? amount;
  final String? errorMessage;
  final String? requestId;

  TransferResult({
    required this.success,
    this.transactionId,
    this.amount,
    this.errorMessage,
    this.requestId,
  });
}

class TransferService {
  final String baseUrl;
  final SecureStorageService _storage;

  TransferService({required this.baseUrl, SecureStorageService? storage})
      : _storage = storage ?? SecureStorageService();

  Future<NetworkResult> lookupRecipient(String email) async {
    final token = await _storage.getToken();
    if (token == null) {
      return NetworkResult(
        success: false,
        errorMessage: 'يرجى تسجيل الدخول أولاً',
      );
    }

    final client = HttpClient();
    try {
      final uri = Uri.parse('$baseUrl/v1/users/lookup/${Uri.encodeComponent(email)}');
      final request = await client.getUrl(uri);
      request.headers.set('Authorization', 'Bearer $token');
      request.headers.set('Accept', 'application/json');
      request.headers.set('X-Request-Id', 'BEZA-${DateTime.now().microsecondsSinceEpoch}');

      final response = await request.close();
      final body = await response.transform(utf8.decoder).join();
      final jsonResponse = json.decode(body) as Map<String, dynamic>;
      final success = jsonResponse['success'] as bool? ?? false;

      if (success && jsonResponse['data'] != null) {
        final data = jsonResponse['data'] as Map<String, dynamic>;
        return NetworkResult(
          success: true,
          data: data,
          requestId: jsonResponse['request_id'] as String?,
        );
      }

      return NetworkResult(
        success: false,
        errorMessage: jsonResponse['message'] as String? ?? 'لم يتم العثور على المستخدم',
        requestId: jsonResponse['request_id'] as String?,
      );
    } on SocketException {
      return NetworkResult(
        success: false,
        errorMessage: 'تعذر الاتصال بالخادم. يرجى المحاولة لاحقاً.',
      );
    } catch (e) {
      return NetworkResult(
        success: false,
        errorMessage: 'حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.',
      );
    } finally {
      client.close();
    }
  }

  Future<TransferResult> transfer({
    required String toWalletId,
    required Money amount,
    required String currency,
  }) async {
    final token = await _storage.getToken();
    if (token == null) {
      return TransferResult(
        success: false,
        errorMessage: 'يرجى تسجيل الدخول أولاً',
      );
    }

    final client = HttpClient();
    try {
      final request = await client.postUrl(Uri.parse('$baseUrl/v1/wallet/transfer'));
      request.headers.set('Authorization', 'Bearer $token');
      request.headers.set('Content-Type', 'application/json');
      request.headers.set('Accept', 'application/json');
      request.headers.set('X-Request-Id', 'BEZA-${DateTime.now().microsecondsSinceEpoch}');

      final body = json.encode({
        'to_wallet_id': toWalletId,
        'amount_fils': amount.fils,
        'currency': currency,
      });
      request.write(body);

      final response = await request.close();
      final responseBody = await response.transform(utf8.decoder).join();
      final jsonResponse = json.decode(responseBody) as Map<String, dynamic>;
      final success = jsonResponse['success'] as bool? ?? false;

      if (success && jsonResponse['data'] != null) {
        final data = jsonResponse['data'] as Map<String, dynamic>;
        return TransferResult(
          success: true,
          transactionId: data['entry_id'] as String?,
          amount: Money.fromFils(data['amount_fils'] as int),
          requestId: jsonResponse['request_id'] as String?,
        );
      }

      final message = jsonResponse['message'] as String? ?? 'فشل التحويل';
      final errors = jsonResponse['errors'];
      String errorMessage = message;
      if (errors is Map && errors.isNotEmpty) {
        final firstKey = errors.keys.first;
        final firstError = errors[firstKey];
        if (firstError is List && firstError.isNotEmpty) {
          errorMessage = firstError.first as String;
        }
      }

      return TransferResult(
        success: false,
        errorMessage: errorMessage,
        requestId: jsonResponse['request_id'] as String?,
      );
    } on SocketException {
      return TransferResult(
        success: false,
        errorMessage: 'تعذر الاتصال بالخادم. يرجى المحاولة لاحقاً.',
      );
    } catch (e) {
      return TransferResult(
        success: false,
        errorMessage: 'حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.',
      );
    } finally {
      client.close();
    }
  }
}
