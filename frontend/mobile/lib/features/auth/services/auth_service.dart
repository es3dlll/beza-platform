import 'package:dio/dio.dart';
import '../../../core/api/api_client.dart';

class DeviceInfo {
  final String deviceId;
  final String deviceName;
  final String deviceType;
  final String? fcmToken;

  DeviceInfo({
    required this.deviceId,
    required this.deviceName,
    this.deviceType = 'mobile',
    this.fcmToken,
  });

  Map<String, dynamic> toJson() => {
        'device_id': deviceId,
        'device_name': deviceName,
        'device_type': deviceType,
        if (fcmToken != null) 'fcm_token': fcmToken,
      };
}

class AuthService {
  final ApiClient _client;

  AuthService(this._client);

  Future<Map<String, dynamic>> register({required String phone}) async {
    final response = await _client.post('/auth/register', data: {
      'phone': phone,
      'phone_country_code': '963',
      'locale': 'ar',
    });
    return response.data;
  }

  Future<Map<String, dynamic>> registerWithPassword({
    required String firstName,
    required String lastName,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await _client.post('/auth/register', data: {
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'phone': phone,
      'phone_country_code': '963',
      'password': password,
      'password_confirmation': passwordConfirmation,
      'locale': 'ar',
    });
    return response.data;
  }

  Future<void> sendPhoneVerificationOtp() async {
    await _client.post('/identity/send-phone-verification-otp');
  }

  Future<void> verifyPhoneOtp(String code) async {
    await _client.post('/identity/verify-phone-otp', data: {'code': code});
  }

  Future<Map<String, dynamic>> login({
    required String phone,
    required String pin,
    required DeviceInfo device,
  }) async {
    final response = await _client.post('/auth/login', data: {
      'phone': phone,
      'pin': pin,
      ...device.toJson(),
    });
    return response.data;
  }

  Future<Map<String, dynamic>> requestOtp({
    required String phone,
    required String purpose,
  }) async {
    final response = await _client.post('/auth/otp/request', data: {
      'phone': phone,
      'purpose': purpose,
    });
    return response.data;
  }

  Future<Map<String, dynamic>> verifyOtp({
    required String phone,
    required String code,
    required String purpose,
  }) async {
    final response = await _client.post('/auth/otp/verify', data: {
      'phone': phone,
      'code': code,
      'purpose': purpose,
    });
    return response.data;
  }

  Future<Map<String, dynamic>> createPin({
    required String pin,
    required String pinConfirmation,
  }) async {
    final response = await _client.post('/auth/pin/create', data: {
      'pin': pin,
      'pin_confirmation': pinConfirmation,
    });
    return response.data;
  }

  Future<Map<String, dynamic>> changePin({
    required String currentPin,
    required String newPin,
    required String newPinConfirmation,
  }) async {
    final response = await _client.post('/auth/pin/change', data: {
      'current_pin': currentPin,
      'new_pin': newPin,
      'new_pin_confirmation': newPinConfirmation,
    });
    return response.data;
  }

  Future<Map<String, dynamic>> verifyPin({required String pin}) async {
    final response = await _client.post('/auth/pin/verify', data: {
      'pin': pin,
    });
    return response.data;
  }

  Future<void> logout({String? sessionId}) async {
    try {
      await _client.post('/auth/logout', data: {
        if (sessionId != null) 'session_id': sessionId,
      });
    } on DioException {
      // Token may already be invalid
    }
    await _client.clearToken();
  }

  Future<Map<String, dynamic>> loginWithPassword({
    required String phone,
    required String password,
    String deviceId = '',
    String deviceName = '',
  }) async {
    final response = await _client.post('/auth/login-with-password', data: {
      'phone': phone,
      'password': password,
      if (deviceId.isNotEmpty) 'device_id': deviceId,
      if (deviceName.isNotEmpty) 'device_name': deviceName,
    });
    return response.data;
  }

  Future<void> setToken(String token) => _client.setToken(token);
  Future<void> setRefreshToken(String token) => _client.setRefreshToken(token);
  Future<String?> getToken() => _client.getToken();
  Future<String?> getRefreshToken() => _client.getRefreshToken();
  Future<void> clearToken() => _client.clearToken();
}
