import 'dart:async';
import 'dart:convert' hide json;
import 'dart:io';

import 'package:connectivity_plus/connectivity_plus.dart';

import 'secure_storage_service.dart';

class NetworkResult {
  final bool success;
  final int? statusCode;
  final Map<String, dynamic>? data;
  final String? errorMessage;
  final String? requestId;

  NetworkResult({
    required this.success,
    this.statusCode,
    this.data,
    this.errorMessage,
    this.requestId,
  });
}

class ConnectivityChecker {
  final Connectivity _connectivity = Connectivity();
  bool _isOnline = true;

  bool get isOnline => _isOnline;

  Stream<bool> get onConnectivityChanged =>
      _connectivity.onConnectivityChanged.map((result) {
        _isOnline = result != ConnectivityResult.none;
        return _isOnline;
      });

  Future<bool> check() async {
    final result = await _connectivity.checkConnectivity();
    _isOnline = result != ConnectivityResult.none;
    return _isOnline;
  }
}

class NetworkService {
  final String baseUrl;
  final SecureStorageService _storage;
  final ConnectivityChecker _connectivityChecker;

  NetworkService({required this.baseUrl, ConnectivityChecker? connectivityChecker, SecureStorageService? storage})
      : _storage = storage ?? SecureStorageService(),
        _connectivityChecker = connectivityChecker ?? ConnectivityChecker();

  Future<NetworkResult> get(String path,
      {Map<String, String>? headers}) async {
    if (!await _connectivityChecker.check()) {
      return NetworkResult(
        success: false,
        errorMessage: 'لا يوجد اتصال بالإنترنت. يرجى التحقق من اتصالك والمحاولة مرة أخرى.',
      );
    }

    final client = HttpClient();
    try {
      final request = await client.getUrl(Uri.parse('$baseUrl$path'));
      _addHeaders(request, headers);
      final response = await request.close();
      final body = await response.transform(utf8.decoder).join();
      final responseJson = const JsonCodec().decode(body) as Map<String, dynamic>;
      return _parseResponse(response.statusCode, responseJson);
    } on SocketException {
      return NetworkResult(
        success: false,
        errorMessage: 'تعذر الاتصال بالخادم. يرجى المحاولة لاحقاً.',
      );
    } on HttpException {
      return NetworkResult(
        success: false,
        errorMessage: 'حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.',
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

  Future<NetworkResult> post(String path,
      {Map<String, dynamic>? body, Map<String, String>? headers}) async {
    if (!await _connectivityChecker.check()) {
      return NetworkResult(
        success: false,
        errorMessage: 'لا يوجد اتصال بالإنترنت. يرجى التحقق من اتصالك والمحاولة مرة أخرى.',
      );
    }

    final client = HttpClient();
    try {
      final request = await client.postUrl(Uri.parse('$baseUrl$path'));
      _addHeaders(request, headers, hasBody: true);
      if (body != null) {
        request.write(const JsonCodec().encode(body));
      }
      final response = await request.close();
      final responseBody = await response.transform(utf8.decoder).join();
      final responseJson = const JsonCodec().decode(responseBody) as Map<String, dynamic>;
      return _parseResponse(response.statusCode, responseJson);
    } on SocketException {
      return NetworkResult(
        success: false,
        errorMessage: 'تعذر الاتصال بالخادم. يرجى المحاولة لاحقاً.',
      );
    } on HttpException {
      return NetworkResult(
        success: false,
        errorMessage: 'حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.',
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

  Future<NetworkResult> _parseResponse(
      int statusCode, Map<String, dynamic> json) async {
    final success = json['success'] as bool? ?? false;
    final message = json['message'] as String? ?? '';
    final requestId = json['request_id'] as String?;

    if (statusCode == 401) {
      await _storage.deleteToken();
      return NetworkResult(
        success: false,
        statusCode: statusCode,
        errorMessage: 'انتهت صلاحية الجلسة. يرجى تسجيل الدخول مرة أخرى.',
        requestId: requestId,
      );
    }

    if (!success) {
      final errors = json['errors'];
      String errorMessage = message;
      if (errors is Map && errors.isNotEmpty) {
        final firstKey = errors.keys.first;
        final firstError = errors[firstKey];
        if (firstError is List && firstError.isNotEmpty) {
          errorMessage = firstError.first as String;
        }
      }
      return NetworkResult(
        success: false,
        statusCode: statusCode,
        errorMessage: errorMessage.isNotEmpty ? errorMessage : 'حدث خطأ. يرجى المحاولة مرة أخرى.',
        requestId: requestId,
      );
    }

    return NetworkResult(
      success: true,
      statusCode: statusCode,
      data: json['data'] as Map<String, dynamic>?,
      requestId: requestId,
    );
  }

  Future<void> _addHeaders(
    HttpClientRequest request,
    Map<String, String>? extraHeaders, {
    bool hasBody = false,
  }) async {
    final token = await _storage.getToken();
    if (token != null) {
      request.headers.set('Authorization', 'Bearer $token');
    }
    extraHeaders?.forEach(request.headers.set);
    request.headers.set('Content-Type', 'application/json');
    request.headers.set('Accept', 'application/json');
    request.headers.set(
        'X-Request-Id', 'BEZA-${DateTime.now().microsecondsSinceEpoch}');
  }
}
