import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStorageService {
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  static const _tokenKey = 'auth_token';
  static const _userIdKey = 'user_id';
  static const _userNameKey = 'user_name';
  static const _userEmailKey = 'user_email';

  Future<void> saveToken(String token) async {
    await _storage.write(key: _tokenKey, value: token);
  }

  Future<String?> getToken() async {
    return await _storage.read(key: _tokenKey);
  }

  Future<void> deleteToken() async {
    await _storage.delete(key: _tokenKey);
  }

  Future<void> saveUserData({
    required String id,
    required String name,
    required String email,
  }) async {
    await _storage.write(key: _userIdKey, value: id);
    await _storage.write(key: _userNameKey, value: name);
    await _storage.write(key: _userEmailKey, value: email);
  }

  Future<Map<String, String?>> getUserData() async {
    return {
      'id': await _storage.read(key: _userIdKey),
      'name': await _storage.read(key: _userNameKey),
      'email': await _storage.read(key: _userEmailKey),
    };
  }

  Future<void> clearAll() async {
    await _storage.deleteAll();
  }
}
