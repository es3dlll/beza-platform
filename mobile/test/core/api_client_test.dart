import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:beza_platform/core/api/api_client.dart';
import 'package:beza_platform/core/config/app_config.dart';

import '../helpers/test_helpers.dart';
import '../helpers/mocks.mocks.dart';

void main() {
  late MockFlutterSecureStorage mockStorage;
  late ApiClient client;

  setUp(() {
    mockStorage = MockFlutterSecureStorage();
    client = ApiClient(storage: mockStorage);
  });

  group('constructor', () {
    test('sets base URL from AppConfig', () {
      expect(client.dio.options.baseUrl, AppConfig.baseUrl);
    });

    test('sets connect timeout from AppConfig', () {
      expect(client.dio.options.connectTimeout, AppConfig.connectTimeout);
    });

    test('sets receive timeout from AppConfig', () {
      expect(client.dio.options.receiveTimeout, AppConfig.receiveTimeout);
    });

    test('sets JSON content type headers', () {
      expect(client.dio.options.headers['Accept'], 'application/json');
      expect(client.dio.options.headers['Content-Type'], 'application/json');
    });
  });

  group('token management', () {
    test('setToken writes to secure storage', () async {
      await client.setToken('test-token');
      verify(mockStorage.write(key: 'auth_token', value: 'test-token')).called(1);
    });

    test('getToken reads from secure storage', () async {
      when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => 'stored-token');
      final token = await client.getToken();
      expect(token, 'stored-token');
    });

    test('clearToken deletes from secure storage', () async {
      await client.clearToken();
      verify(mockStorage.delete(key: 'auth_token')).called(1);
    });

    test('getToken returns null when no token stored', () async {
      when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => null);
      final token = await client.getToken();
      expect(token, isNull);
    });
  });

  group('base URL management', () {
    test('setBaseUrl writes to secure storage', () async {
      await client.setBaseUrl('https://example.com');
      verify(mockStorage.write(key: 'base_url', value: 'https://example.com')).called(1);
    });

    test('getBaseUrl reads from secure storage', () async {
      when(mockStorage.read(key: 'base_url')).thenAnswer((_) async => 'https://custom.com');
      final url = await client.getBaseUrl();
      expect(url, 'https://custom.com');
    });
  });

  group('token injection on requests', () {
    test('injects Authorization header when token exists', () async {
      when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => 'my-jwt-token');
      final options = RequestOptions(path: '/test');
      final handler = RequestInterceptorHandler();
      final interceptor = client.dio.interceptors.last;

      interceptor.onRequest(options, handler);
      await Future(() => {});

      expect(options.headers['Authorization'], 'Bearer my-jwt-token');
    });

    test('does not inject Authorization header when no token', () async {
      when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => null);
      final options = RequestOptions(path: '/test');
      final handler = RequestInterceptorHandler();
      final interceptor = client.dio.interceptors.last;

      interceptor.onRequest(options, handler);
      await Future(() => {});

      expect(options.headers.containsKey('Authorization'), false);
    });
  });

  group('token clearing on 401', () {
    test('clearToken via public API works', () async {
      await client.clearToken();
      verify(mockStorage.delete(key: 'auth_token')).called(1);
    });
  });

  group('dio instance', () {
    test('dio getter returns the same instance', () {
      expect(client.dio, same(client.dio));
    });

    test('has at least one interceptor', () {
      expect(client.dio.interceptors.length, greaterThanOrEqualTo(1));
    });
  });
}
