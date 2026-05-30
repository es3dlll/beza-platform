import 'package:dio/dio.dart';

class DeviceInfo {
  final String deviceId;
  final String deviceName;
  final String osVersion;
  final String fcmToken;

  DeviceInfo({
    required this.deviceId,
    required this.deviceName,
    required this.osVersion,
    required this.fcmToken,
  });

  Map<String, dynamic> toJson() => {
        'device_id': deviceId,
        'device_name': deviceName,
        'os_version': osVersion,
        'fcm_token': fcmToken,
      };
}

class AuthResponse {
  final String userId;
  final bool otpRequired;

  AuthResponse({required this.userId, required this.otpRequired});

  factory AuthResponse.fromJson(Map<String, dynamic> json) => AuthResponse(
        userId: json['user_id'] as String,
        otpRequired: json['otp_required'] as bool? ?? true,
      );
}

class OtpResponse {
  final String otpId;
  final int expiresInSeconds;
  final String? maskedPhone;

  OtpResponse({
    required this.otpId,
    required this.expiresInSeconds,
    this.maskedPhone,
  });

  factory OtpResponse.fromJson(Map<String, dynamic> json) => OtpResponse(
        otpId: json['otp_id'] as String,
        expiresInSeconds: json['expires_in'] as int? ?? 300,
        maskedPhone: json['masked_phone'] as String?,
      );
}

class VerifyOtpResponse {
  final String userId;

  VerifyOtpResponse({required this.userId});

  factory VerifyOtpResponse.fromJson(Map<String, dynamic> json) =>
      VerifyOtpResponse(userId: json['user_id'] as String);
}

class PinResponse {
  final bool success;

  PinResponse({required this.success});

  factory PinResponse.fromJson(Map<String, dynamic> json) =>
      PinResponse(success: json['success'] as bool? ?? true);
}

class LoginResponse {
  final String token;
  final String refreshToken;
  final String userId;
  final bool biometricEnabled;

  LoginResponse({
    required this.token,
    required this.refreshToken,
    required this.userId,
    this.biometricEnabled = false,
  });

  factory LoginResponse.fromJson(Map<String, dynamic> json) => LoginResponse(
        token: json['token'] as String,
        refreshToken: json['refresh_token'] as String,
        userId: json['user_id'] as String,
        biometricEnabled: json['biometric_enabled'] as bool? ?? false,
      );
}

class AuthService {
  final Dio _dio;

  AuthService(this._dio);

  Future<AuthResponse> register(String phone) async {
    try {
      final response = await _dio.post('/auth/register', data: {
        'phone': phone,
      });
      return AuthResponse.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<OtpResponse> sendOtp(String phone) async {
    try {
      final response = await _dio.post('/auth/otp/send', data: {
        'phone': phone,
      });
      return OtpResponse.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<VerifyOtpResponse> verifyOtp(String phone, String code) async {
    try {
      final response = await _dio.post('/auth/otp/verify', data: {
        'phone': phone,
        'code': code,
      });
      return VerifyOtpResponse.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<PinResponse> createPin(String pin) async {
    try {
      final response = await _dio.post('/auth/pin/create', data: {
        'pin': pin,
      });
      return PinResponse.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<LoginResponse> login(
      String phone, String pin, DeviceInfo device) async {
    try {
      final response = await _dio.post('/auth/login', data: {
        'phone': phone,
        'pin': pin,
        'device': device.toJson(),
      });
      return LoginResponse.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<void> logout(String refreshToken) async {
    try {
      await _dio.post('/auth/logout', data: {
        'refresh_token': refreshToken,
      });
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<LoginResponse> refreshToken(String refreshToken) async {
    try {
      final response = await _dio.post('/auth/refresh', data: {
        'refresh_token': refreshToken,
      });
      return LoginResponse.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Exception _handleError(DioException e) {
    try {
      final body = e.response?.data as Map<String, dynamic>?;
      final messageAr = body?['message_ar'] as String? ?? 'حدث خطأ غير متوقع';
      final messageEn = body?['message'] as String? ?? 'An unexpected error occurred';
      return AuthException(
        message: messageEn,
        messageAr: messageAr,
        statusCode: e.response?.statusCode,
      );
    } catch (_) {
      return AuthException(
        message: 'Network error',
        messageAr: 'خطأ في الاتصال بالشبكة',
      );
    }
  }
}

class AuthException implements Exception {
  final String message;
  final String messageAr;
  final int? statusCode;

  AuthException({
    required this.message,
    required this.messageAr,
    this.statusCode,
  });

  @override
  String toString() => messageAr;
}
