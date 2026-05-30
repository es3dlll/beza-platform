import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import '../services/auth_service.dart';

enum AuthStep { phone, otp, pinCreate, pinEntry, biometric, home }

class AuthState {
  final bool isLoading;
  final bool isAuthenticated;
  final String? userId;
  final String? token;
  final String? errorMessage;
  final String? errorMessageAr;
  final AuthStep currentStep;
  final String? phone;
  final String? otpId;
  final int otpRemainingSeconds;
  final int pinAttemptsRemaining;
  final bool isLocked;
  final bool biometricEnabled;
  final int? lastLockedAt;

  const AuthState({
    required this.isLoading,
    required this.isAuthenticated,
    this.userId,
    this.token,
    this.errorMessage,
    this.errorMessageAr,
    required this.currentStep,
    this.phone,
    this.otpId,
    this.otpRemainingSeconds = 0,
    this.pinAttemptsRemaining = 5,
    this.isLocked = false,
    this.biometricEnabled = false,
    this.lastLockedAt,
  });

  const AuthState.initial()
      : isLoading = false,
        isAuthenticated = false,
        userId = null,
        token = null,
        errorMessage = null,
        errorMessageAr = null,
        currentStep = AuthStep.phone,
        phone = null,
        otpId = null,
        otpRemainingSeconds = 0,
        pinAttemptsRemaining = 5,
        isLocked = false,
        biometricEnabled = false,
        lastLockedAt = null;

  AuthState copyWith({
    bool? isLoading,
    bool? isAuthenticated,
    String? userId,
    String? token,
    String? errorMessage,
    String? errorMessageAr,
    AuthStep? currentStep,
    String? phone,
    String? otpId,
    int? otpRemainingSeconds,
    int? pinAttemptsRemaining,
    bool? isLocked,
    bool? biometricEnabled,
    int? lastLockedAt,
    bool clearErrors = false,
  }) {
    return AuthState(
      isLoading: isLoading ?? this.isLoading,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      userId: userId ?? this.userId,
      token: token ?? this.token,
      errorMessage: clearErrors ? null : (errorMessage ?? this.errorMessage),
      errorMessageAr:
          clearErrors ? null : (errorMessageAr ?? this.errorMessageAr),
      currentStep: currentStep ?? this.currentStep,
      phone: phone ?? this.phone,
      otpId: otpId ?? this.otpId,
      otpRemainingSeconds: otpRemainingSeconds ?? this.otpRemainingSeconds,
      pinAttemptsRemaining:
          pinAttemptsRemaining ?? this.pinAttemptsRemaining,
      isLocked: isLocked ?? this.isLocked,
      biometricEnabled: biometricEnabled ?? this.biometricEnabled,
      lastLockedAt: lastLockedAt ?? this.lastLockedAt,
    );
  }
}

class AuthProvider extends StateNotifier<AuthState> {
  final AuthService _authService;

  AuthProvider(this._authService) : super(AuthState.initial());

  Future<void> register(String phone) async {
    state = state.copyWith(
      isLoading: true,
      clearErrors: true,
      phone: phone,
    );
    try {
      await _authService.register(phone);
      await sendOtp(phone);
    } on AuthException catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.message,
        errorMessageAr: e.messageAr,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Registration failed',
        errorMessageAr: 'فشل في التسجيل',
      );
    }
  }

  Future<void> sendOtp(String phone) async {
    state = state.copyWith(
      isLoading: true,
      clearErrors: true,
    );
    try {
      final response = await _authService.sendOtp(phone);
      state = state.copyWith(
        isLoading: false,
        otpId: response.otpId,
        otpRemainingSeconds: response.expiresInSeconds,
        currentStep: AuthStep.otp,
      );
    } on AuthException catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.message,
        errorMessageAr: e.messageAr,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Failed to send OTP',
        errorMessageAr: 'فشل في إرسال رمز التحقق',
      );
    }
  }

  Future<void> verifyOtp(String phone, String code) async {
    state = state.copyWith(
      isLoading: true,
      clearErrors: true,
    );
    try {
      await _authService.verifyOtp(phone, code);
      state = state.copyWith(
        isLoading: false,
        currentStep: AuthStep.pinCreate,
      );
    } on AuthException catch (e) {
      if (e.message.contains('expired') || e.messageAr.contains('انتهت')) {
        state = state.copyWith(
          isLoading: false,
          errorMessage: e.message,
          errorMessageAr: e.messageAr,
          otpRemainingSeconds: 0,
        );
      } else {
        state = state.copyWith(
          isLoading: false,
          errorMessage: e.message,
          errorMessageAr: e.messageAr,
        );
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Verification failed',
        errorMessageAr: 'فشل التحقق',
      );
    }
  }

  Future<void> createPin(String pin) async {
    state = state.copyWith(
      isLoading: true,
      clearErrors: true,
    );
    try {
      await _authService.createPin(pin);
      state = state.copyWith(
        isLoading: false,
        currentStep: AuthStep.biometric,
      );
    } on AuthException catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.message,
        errorMessageAr: e.messageAr,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Failed to create PIN',
        errorMessageAr: 'فشل في إنشاء رمز PIN',
      );
    }
  }

  Future<void> login(String phone, String pin) async {
    if (state.isLocked) {
      final now = DateTime.now().millisecondsSinceEpoch;
      final lockDuration = 30 * 60 * 1000;
      if (state.lastLockedAt != null &&
          now - state.lastLockedAt! < lockDuration) {
        state = state.copyWith(
          errorMessage: 'Account locked. Try again in 30 minutes.',
          errorMessageAr: 'تم قفل الحساب. حاول بعد 30 دقيقة',
        );
        return;
      }
      state = state.copyWith(
        isLocked: false,
        pinAttemptsRemaining: 5,
      );
    }

    state = state.copyWith(
      isLoading: true,
      clearErrors: true,
    );
    try {
      final device = DeviceInfo(
        deviceId: 'device_id_placeholder',
        deviceName: 'Unknown',
        osVersion: 'Unknown',
        fcmToken: 'fcm_token_placeholder',
      );
      final response = await _authService.login(phone, pin, device);
      state = state.copyWith(
        isLoading: false,
        isAuthenticated: true,
        token: response.token,
        userId: response.userId,
        currentStep: AuthStep.home,
        biometricEnabled: response.biometricEnabled,
      );
    } on AuthException catch (e) {
      final remaining = state.pinAttemptsRemaining - 1;
      if (remaining <= 0) {
        state = state.copyWith(
          isLoading: false,
          isLocked: true,
          pinAttemptsRemaining: 0,
          lastLockedAt: DateTime.now().millisecondsSinceEpoch,
          errorMessage: 'Account locked. Try again in 30 minutes.',
          errorMessageAr: 'تم قفل الحساب. حاول بعد 30 دقيقة',
        );
      } else {
        state = state.copyWith(
          isLoading: false,
          pinAttemptsRemaining: remaining,
          errorMessage: e.message,
          errorMessageAr: e.messageAr,
        );
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Login failed',
        errorMessageAr: 'فشل تسجيل الدخول',
      );
    }
  }

  Future<void> loginWithBiometric() async {
    state = state.copyWith(isLoading: true, clearErrors: true);
    try {
      final device = DeviceInfo(
        deviceId: 'device_id_placeholder',
        deviceName: 'Unknown',
        osVersion: 'Unknown',
        fcmToken: 'fcm_token_placeholder',
      );
      final response = await _authService.login(
        state.phone ?? '',
        '',
        device,
      );
      state = state.copyWith(
        isLoading: false,
        isAuthenticated: true,
        token: response.token,
        userId: response.userId,
        currentStep: AuthStep.home,
      );
    } on AuthException catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.message,
        errorMessageAr: e.messageAr,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Biometric login failed',
        errorMessageAr: 'فشل تسجيل الدخول بالبصمة',
      );
    }
  }

  Future<void> setBiometricEnabled(bool enabled) async {
    state = state.copyWith(biometricEnabled: enabled);
  }

  void setPinEntryStep() {
    state = state.copyWith(
      currentStep: AuthStep.pinEntry,
      clearErrors: true,
      pinAttemptsRemaining: 5,
      isLocked: false,
    );
  }

  void setPhoneStep() {
    state = state.copyWith(
      currentStep: AuthStep.phone,
      clearErrors: true,
    );
  }

  Future<void> logout() async {
    try {
      if (state.token != null) {
        await _authService.logout(state.token!);
      }
    } catch (_) {}
    state = AuthState.initial();
  }

  Future<void> refreshToken() async {
    try {
      final refresh = state.token;
      if (refresh == null) return;
      final response = await _authService.refreshToken(refresh);
      state = state.copyWith(token: response.token);
    } on AuthException {
      state = AuthState.initial();
    }
  }

  void clearErrors() {
    state = state.copyWith(clearErrors: true);
  }
}
