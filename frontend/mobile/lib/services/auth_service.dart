import 'dart:convert';
import 'dart:io';

import 'network_service.dart';
import 'secure_storage_service.dart';

class AuthService {
  final String baseUrl;
  final SecureStorageService _storage = SecureStorageService();
  late final NetworkService _network;

  AuthService({required this.baseUrl}) {
    _network = NetworkService(baseUrl: baseUrl);
  }

  bool _isAuthenticated = false;

  bool get isAuthenticated => _isAuthenticated;

  Future<void> checkAuthStatus() async {
    final token = await _storage.getToken();
    _isAuthenticated = token != null;
  }

  Future<NetworkResult> login(String email, String password) async {
    final client = HttpClient();
    try {
      final request = await client.postUrl(Uri.parse('$baseUrl/v1/auth/login'));
      request.headers.set('Content-Type', 'application/json');
      request.headers.set('Accept', 'application/json');
      request.write(json.encode({'email': email, 'password': password}));
      final response = await request.close();
      final body = await response.transform(utf8.decoder).join();
      final jsonResponse = json.decode(body) as Map<String, dynamic>;

      final success = jsonResponse['success'] as bool? ?? false;

      if (success && jsonResponse['data'] != null) {
        final data = jsonResponse['data'] as Map<String, dynamic>;
        final token = data['token'] as String?;
        final user = data['user'] as Map<String, dynamic>?;

        if (token != null) {
          await _storage.saveToken(token);
          _isAuthenticated = true;
        }

        if (user != null) {
          await _storage.saveUserData(
            id: user['id'] as String? ?? '',
            name: user['name'] as String? ?? '',
            email: user['email'] as String? ?? '',
          );
        }

        return NetworkResult(
          success: true,
          data: data,
          requestId: jsonResponse['request_id'] as String?,
        );
      }

      return NetworkResult(
        success: false,
        errorMessage: jsonResponse['message'] as String? ?? 'فشل تسجيل الدخول',
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

  Future<NetworkResult> register(
      String name, String email, String password) async {
    final client = HttpClient();
    try {
      final request = await client.postUrl(
          Uri.parse('$baseUrl/v1/auth/register'));
      request.headers.set('Content-Type', 'application/json');
      request.headers.set('Accept', 'application/json');
      request.write(json.encode({
        'name': name,
        'email': email,
        'password': password,
      }));
      final response = await request.close();
      final body = await response.transform(utf8.decoder).join();
      final jsonResponse = json.decode(body) as Map<String, dynamic>;

      final success = jsonResponse['success'] as bool? ?? false;

      if (success && jsonResponse['data'] != null) {
        final data = jsonResponse['data'] as Map<String, dynamic>;
        final token = data['token'] as String?;
        final user = data['user'] as Map<String, dynamic>?;

        if (token != null) {
          await _storage.saveToken(token);
          _isAuthenticated = true;
        }

        if (user != null) {
          await _storage.saveUserData(
            id: user['id'] as String? ?? '',
            name: user['name'] as String? ?? '',
            email: user['email'] as String? ?? '',
          );
        }

        return NetworkResult(
          success: true,
          data: data,
          requestId: jsonResponse['request_id'] as String?,
        );
      }

      return NetworkResult(
        success: false,
        errorMessage: jsonResponse['message'] as String? ?? 'فشل إنشاء الحساب',
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

  Future<void> logout() async {
    await _storage.clearAll();
    _isAuthenticated = false;
  }
}
