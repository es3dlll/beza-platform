import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../services/auth_service.dart';

enum AuthStep { phone, otp, pinCreate, pinEntry, biometric, home, register, securitySetup }

class AuthState {
  final AuthStep currentStep;
  final String phone;
  final String otpCode;
  final String? token;
  final bool isLoading;
  final String? error;
  final bool isAuthenticated;
  final String? registeredPhone;
  final bool isPhoneVerified;
  final bool isSecuritySetupComplete;
  final bool pendingAccount;
  final bool isBiometricEnabled;

  const AuthState({
    this.currentStep = AuthStep.phone,
    this.phone = '',
    this.otpCode = '',
    this.token,
    this.isLoading = false,
    this.error,
    this.isAuthenticated = false,
    this.registeredPhone,
    this.isPhoneVerified = false,
    this.isSecuritySetupComplete = false,
    this.pendingAccount = false,
    this.isBiometricEnabled = false,
  });

  AuthState copyWith({
    AuthStep? currentStep,
    String? phone,
    String? otpCode,
    String? token,
    bool? isLoading,
    String? error,
    bool? isAuthenticated,
    String? registeredPhone,
    bool? isPhoneVerified,
    bool? isSecuritySetupComplete,
    bool? pendingAccount,
    bool? isBiometricEnabled,
  }) {
    return AuthState(
      currentStep: currentStep ?? this.currentStep,
      phone: phone ?? this.phone,
      otpCode: otpCode ?? this.otpCode,
      token: token ?? this.token,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      registeredPhone: registeredPhone ?? this.registeredPhone,
      isPhoneVerified: isPhoneVerified ?? this.isPhoneVerified,
      isSecuritySetupComplete: isSecuritySetupComplete ?? this.isSecuritySetupComplete,
      pendingAccount: pendingAccount ?? this.pendingAccount,
      isBiometricEnabled: isBiometricEnabled ?? this.isBiometricEnabled,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final AuthService _authService;

  AuthNotifier(this._authService) : super(const AuthState()) {
    checkAuthStatus();
  }

  Future<void> checkAuthStatus() async {
    final token = await _authService.getToken();
    if (token != null) {
      state = state.copyWith(token: token, isAuthenticated: true);
    }
  }

  void _storeTokens(String token, String refreshToken) {
    _authService.setToken(token);
    _authService.setRefreshToken(refreshToken);
    state = state.copyWith(token: token, isAuthenticated: true);
  }

  Future<void> register(String phone) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _authService.register(phone: phone);
      final token = result['data']?['token'] as String? ?? result['token'] as String?;
      final refreshToken = result['data']?['refresh_token'] as String? ?? result['refresh_token'] as String?;
      if (token != null) {
        _storeTokens(token, refreshToken ?? '');
      }
      final hasPass = result['data']?['has_password'] == true;
      state = state.copyWith(
        isLoading: false,
        phone: phone,
        currentStep: hasPass ? AuthStep.pinEntry : AuthStep.register,
        registeredPhone: result['data']?['phone'] as String? ?? phone,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: _extractError(e));
    }
  }

  Future<void> registerWithPassword({
    required String phone,
    required String firstName,
    required String lastName,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _authService.registerWithPassword(
        phone: phone,
        firstName: firstName,
        lastName: lastName,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );
      final token = result['data']?['token'] as String? ?? result['token'] as String?;
      final refreshToken = result['data']?['refresh_token'] as String? ?? result['refresh_token'] as String?;
      if (token != null) {
        _storeTokens(token, refreshToken ?? '');
      }
      await loginWithPassword(phone: phone, password: password);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: _extractError(e));
    }
  }

  Future<void> loginWithPassword({required String phone, required String password}) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _authService.loginWithPassword(phone: phone, password: password);
      final token = result['data']?['token'] as String? ?? result['token'] as String?;
      final refreshToken = result['data']?['refresh_token'] as String? ?? result['refresh_token'] as String?;
      _storeTokens(token ?? '', refreshToken ?? '');
      state = state.copyWith(isLoading: false, currentStep: AuthStep.home, isAuthenticated: true);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: _extractError(e));
    }
  }

  Future<void> loginWithPin({required String phone, required String pin}) async {
    state = state.copyWith(isLoading: true, error: null, phone: phone);
    try {
      final result = await _authService.login(
        phone: phone,
        pin: pin,
        device: DeviceInfo(deviceId: '', deviceName: ''),
      );
      final token = result['data']?['token'] as String? ?? result['token'] as String?;
      final refreshToken = result['data']?['refresh_token'] as String? ?? result['refresh_token'] as String?;
      _storeTokens(token ?? '', refreshToken ?? '');
      state = state.copyWith(isLoading: false, currentStep: AuthStep.home, isAuthenticated: true);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: _extractError(e));
    }
  }

  Future<void> requestOtp(String phone) async {
    state = state.copyWith(isLoading: true, error: null, phone: phone);
    try {
      await _authService.requestOtp(phone: phone, purpose: 'auth');
      state = state.copyWith(isLoading: false, currentStep: AuthStep.otp);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: _extractError(e));
    }
  }

  Future<void> verifyOtp(String phone, String code) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      await _authService.verifyOtp(phone: phone, code: code, purpose: 'auth');
      state = state.copyWith(isLoading: false, currentStep: AuthStep.pinCreate);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: _extractError(e));
    }
  }

  Future<void> createPin(String pin, String pinConfirmation) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _authService.createPin(pin: pin, pinConfirmation: pinConfirmation);
      final token = result['data']?['token'] as String? ?? result['token'] as String?;
      final refreshToken = result['data']?['refresh_token'] as String? ?? result['refresh_token'] as String?;
      if (token != null) {
        _storeTokens(token, refreshToken ?? '');
      }
      state = state.copyWith(isLoading: false, currentStep: AuthStep.biometric);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: _extractError(e));
    }
  }

  void enableBiometric() {
    state = state.copyWith(isBiometricEnabled: true);
  }

  void skipBiometric() {
    state = state.copyWith(isBiometricEnabled: false);
  }

  void completeSecuritySetup() {
    state = state.copyWith(isSecuritySetupComplete: true);
  }

  Future<void> logout() async {
    await _authService.logout();
    state = const AuthState();
  }

  Future<void> sendPhoneVerificationOtp() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      await _authService.sendPhoneVerificationOtp();
      state = state.copyWith(isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: _extractError(e));
    }
  }

  Future<void> verifyPhoneOtp(String code) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      await _authService.verifyPhoneOtp(code);
      state = state.copyWith(isLoading: false, isPhoneVerified: true);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: _extractError(e));
    }
  }

  String _extractError(dynamic e) {
    if (e is DioException) {
      final data = e.response?.data;
      if (data is Map) {
        return (data['error']?['message_ar'] as String?) ??
            (data['error']?['message'] as String?) ??
            (data['message'] as String?) ??
            'حدث خطأ أثناء تحميل البيانات';
      }
    }
    return e.toString().replaceFirst('Exception: ', '').replaceFirst('DioException: ', '');
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  final api = ApiClient();
  final service = AuthService(api);
  return AuthNotifier(service);
});
