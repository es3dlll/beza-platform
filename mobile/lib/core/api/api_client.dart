import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/app_config.dart';

class ApiClient {
  static const _baseUrlKey = 'base_url';
  static const _tokenKey = 'auth_token';
  static const _refreshTokenKey = 'refresh_token';

  late final Dio _dio;
  final FlutterSecureStorage _storage;
  bool _refreshing = false;
  final List<_PendingRequest> _pending = [];

  ApiClient({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage() {
    _dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.baseUrl,
        connectTimeout: AppConfig.connectTimeout,
        receiveTimeout: AppConfig.receiveTimeout,
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _storage.read(key: _tokenKey);
          debugPrint('[API] ${options.method} ${options.uri} token=${token != null ? '✓' : '✗'}');
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onResponse: (response, handler) {
          debugPrint('[API] ${response.statusCode} ${response.requestOptions.uri}');
          handler.next(response);
        },
        onError: (error, handler) async {
          debugPrint('[API] ERROR ${error.response?.statusCode} ${error.requestOptions.uri}: ${error.message}');
          if (error.response?.statusCode != 401) {
            handler.next(error);
            return;
          }

          if (_refreshing) {
            _pending.add(_PendingRequest(options: error.requestOptions, handler: handler));
            return;
          }

          _refreshing = true;
          try {
            final refreshToken = await _storage.read(key: _refreshTokenKey);
            if (refreshToken == null) throw Exception('No refresh token');

            final refreshDio = Dio(BaseOptions(baseUrl: AppConfig.baseUrl, headers: {'Accept': 'application/json'}));
            final refreshRes = await refreshDio.post('/auth/refresh', data: {'refresh_token': refreshToken});
            final data = refreshRes.data;
            final newToken = data['data']?['token'] as String?;
            final newRefreshToken = data['data']?['refresh_token'] as String?;

            if (newToken == null) throw Exception('Refresh succeeded but no token returned');

            await _storage.write(key: _tokenKey, value: newToken);
            if (newRefreshToken != null) {
              await _storage.write(key: _refreshTokenKey, value: newRefreshToken);
            }

            error.requestOptions.headers['Authorization'] = 'Bearer $newToken';
            final retryResponse = await _dio.fetch(error.requestOptions);
            handler.resolve(retryResponse);

            for (final pending in _pending) {
              pending.options.headers['Authorization'] = 'Bearer $newToken';
              try {
                final response = await _dio.fetch(pending.options);
                pending.handler.resolve(response);
              } catch (e) {
                pending.handler.next(e as DioException);
              }
            }
          } catch (_) {
            await _storage.delete(key: _tokenKey);
            await _storage.delete(key: _refreshTokenKey);
            handler.next(error);
            for (final pending in _pending) {
              pending.handler.next(error);
            }
          } finally {
            _refreshing = false;
            _pending.clear();
          }
        },
      ),
    );
  }

  Dio get dio => _dio;

  Future<void> setToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  Future<void> setRefreshToken(String token) =>
      _storage.write(key: _refreshTokenKey, value: token);

  Future<void> clearToken() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _refreshTokenKey);
  }

  Future<String?> getToken() => _storage.read(key: _tokenKey);

  Future<String?> getRefreshToken() => _storage.read(key: _refreshTokenKey);

  Future<void> setBaseUrl(String url) =>
      _storage.write(key: _baseUrlKey, value: url);

  Future<String?> getBaseUrl() => _storage.read(key: _baseUrlKey);

  Future<Response> get(
    String path, {
    Map<String, dynamic>? queryParameters,
  }) =>
      _dio.get(path, queryParameters: queryParameters);

  Future<Response> post(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) =>
      _dio.post(path, data: data, queryParameters: queryParameters);

  Future<Response> put(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) =>
      _dio.put(path, data: data, queryParameters: queryParameters);

  Future<Response> patch(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) =>
      _dio.patch(path, data: data, queryParameters: queryParameters);

  Future<Response> delete(
    String path, {
    dynamic data,
  }) =>
      _dio.delete(path, data: data);

  Future<void> updateFcmToken(String token) =>
      post('/notifications/fcm-token', data: {'token': token, 'platform': 'android'});
}

class _PendingRequest {
  final RequestOptions options;
  final ErrorInterceptorHandler handler;
  _PendingRequest({required this.options, required this.handler});
}
