import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:beza_platform/features/auth/providers/auth_provider.dart';
import 'package:beza_platform/features/auth/services/auth_service.dart';

import '../../helpers/test_helpers.dart';

void main() {
  late MockAuthService mockAuthService;
  late AuthNotifier authNotifier;

  setUp(() {
    mockAuthService = MockAuthService();
    authNotifier = AuthNotifier(mockAuthService);
  });

  group('initial state', () {
    test('has correct default values', () {
      expect(authNotifier.state.currentStep, AuthStep.phone);
      expect(authNotifier.state.phone, '');
      expect(authNotifier.state.isLoading, false);
      expect(authNotifier.state.isAuthenticated, false);
      expect(authNotifier.state.token, isNull);
      expect(authNotifier.state.error, isNull);
    });
  });

  group('register', () {
    test('success sets register step when no password', () async {
      when(mockAuthService.register(phone: '963900000001'))
          .thenAnswer((_) async => {});

      await authNotifier.register('963900000001');

      expect(authNotifier.state.currentStep, AuthStep.register);
      expect(authNotifier.state.isLoading, false);
      expect(authNotifier.state.phone, '963900000001');
      expect(authNotifier.state.registeredPhone, '963900000001');
      expect(authNotifier.state.error, isNull);
    });

    test('DioException sets parsed error message', () async {
      when(mockAuthService.register(phone: '963900000001')).thenThrow(
        createDioException(
          statusCode: 422,
          data: {'error': {'message_ar': 'رقم الجوال مسجل مسبقاً'}},
        ),
      );

      await authNotifier.register('963900000001');

      expect(authNotifier.state.isLoading, false);
      expect(authNotifier.state.error, isNotNull);
      expect(authNotifier.state.currentStep, AuthStep.phone);
    });

    test('connection timeout returns raw message', () async {
      when(mockAuthService.register(phone: '963900000001')).thenThrow(
        createDioException(type: DioExceptionType.connectionTimeout),
      );

      await authNotifier.register('963900000001');

      expect(authNotifier.state.isLoading, false);
      expect(authNotifier.state.error, isNotNull);
    });

    test('connection error returns raw message', () async {
      when(mockAuthService.register(phone: '963900000001')).thenThrow(
        createDioException(type: DioExceptionType.connectionError),
      );

      await authNotifier.register('963900000001');

      expect(authNotifier.state.isLoading, false);
      expect(authNotifier.state.error, isNotNull);
    });
  });

  group('requestOtp', () {
    test('success sets otp step', () async {
      when(mockAuthService.requestOtp(
        phone: '963900000001',
        purpose: 'auth',
      )).thenAnswer((_) async => {});

      await authNotifier.requestOtp('963900000001');

      expect(authNotifier.state.currentStep, AuthStep.otp);
      expect(authNotifier.state.isLoading, false);
    });

    test('error sets error message', () async {
      when(mockAuthService.requestOtp(
        phone: '963900000001',
        purpose: 'auth',
      )).thenThrow(Exception('fail'));

      await authNotifier.requestOtp('963900000001');

      expect(authNotifier.state.isLoading, false);
      expect(authNotifier.state.error, isNotNull);
    });
  });

  group('verifyOtp', () {
    test('success sets pinCreate step', () async {
      when(mockAuthService.verifyOtp(
        phone: '963900000001',
        code: '123456',
        purpose: 'auth',
      )).thenAnswer((_) async => {});

      await authNotifier.verifyOtp('963900000001', '123456');

      expect(authNotifier.state.currentStep, AuthStep.pinCreate);
      expect(authNotifier.state.isLoading, false);
    });

    test('error sets error message', () async {
      when(mockAuthService.verifyOtp(
        phone: '963900000001',
        code: '000000',
        purpose: 'auth',
      )).thenThrow(Exception('fail'));

      await authNotifier.verifyOtp('963900000001', '000000');

      expect(authNotifier.state.isLoading, false);
      expect(authNotifier.state.error, isNotNull);
    });
  });

  group('createPin', () {
    test('success creates pin and stores token', () async {
      when(mockAuthService.createPin(pin: '123456', pinConfirmation: '123456'))
          .thenAnswer((_) async => {'data': {'token': 'auth-token-123'}});

      when(mockAuthService.setToken('auth-token-123')).thenAnswer((_) async => {});
      when(mockAuthService.setRefreshToken('')).thenAnswer((_) async => {});

      await authNotifier.createPin('123456', '123456');

      expect(authNotifier.state.isLoading, false);
      expect(authNotifier.state.currentStep, AuthStep.biometric);
    });

    test('error falls back to error', () async {
      when(mockAuthService.createPin(pin: '123456', pinConfirmation: '123456'))
          .thenThrow(Exception('create pin failed'));

      await authNotifier.createPin('123456', '123456');

      expect(authNotifier.state.isLoading, false);
      expect(authNotifier.state.error, isNotNull);
    });
  });

  group('loginWithPin', () {
    test('success sets authenticated and home step', () async {
      when(mockAuthService.login(
        phone: '963900000001',
        pin: '123456',
        device: argThat(isA<DeviceInfo>(), named: 'device'),
      )).thenAnswer((_) async => {'data': {'token': 'login-token'}});

      when(mockAuthService.setToken('login-token')).thenAnswer((_) async => {});
      when(mockAuthService.setRefreshToken('')).thenAnswer((_) async => {});

      await authNotifier.loginWithPin(phone: '963900000001', pin: '123456');

      expect(authNotifier.state.isAuthenticated, true);
      expect(authNotifier.state.token, 'login-token');
      expect(authNotifier.state.currentStep, AuthStep.home);
      expect(authNotifier.state.isLoading, false);
    });

    test('error with empty phone shows error', () async {
      when(mockAuthService.login(
        phone: '',
        pin: '123456',
        device: argThat(isA<DeviceInfo>(), named: 'device'),
      )).thenThrow(Exception('phone is required'));

      await authNotifier.loginWithPin(phone: '', pin: '123456');

      expect(authNotifier.state.isLoading, false);
      expect(authNotifier.state.error, isNotNull);
    });

    test('error sets error message', () async {
      when(mockAuthService.login(
        phone: '963900000001',
        pin: '000000',
        device: argThat(isA<DeviceInfo>(), named: 'device'),
      )).thenThrow(createDioException(statusCode: 401));

      await authNotifier.loginWithPin(phone: '963900000001', pin: '000000');

      expect(authNotifier.state.isLoading, false);
      expect(authNotifier.state.error, isNotNull);
    });
  });

  group('logout', () {
    test('resets state to initial', () async {
      when(mockAuthService.logout()).thenAnswer((_) async => {});

      await authNotifier.logout();

      expect(authNotifier.state.currentStep, AuthStep.phone);
      expect(authNotifier.state.phone, '');
      expect(authNotifier.state.isAuthenticated, false);
      expect(authNotifier.state.token, isNull);
    });
  });

  group('checkAuthStatus', () {
    test('sets authenticated when token exists', () async {
      when(mockAuthService.getToken()).thenAnswer((_) async => 'existing-token');

      await authNotifier.checkAuthStatus();

      expect(authNotifier.state.isAuthenticated, true);
      expect(authNotifier.state.token, 'existing-token');
    });

    test('stays unauthenticated when no token', () async {
      when(mockAuthService.getToken()).thenAnswer((_) async => null);

      await authNotifier.checkAuthStatus();

      expect(authNotifier.state.isAuthenticated, false);
      expect(authNotifier.state.token, isNull);
    });
  });

  group('biometric', () {
    test('skipBiometric sets isBiometricEnabled false', () {
      authNotifier.skipBiometric();
      expect(authNotifier.state.isBiometricEnabled, false);
    });

    test('enableBiometric sets isBiometricEnabled true', () {
      authNotifier.enableBiometric();
      expect(authNotifier.state.isBiometricEnabled, true);
    });
  });
}
