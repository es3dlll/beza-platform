import 'package:flutter_test/flutter_test.dart';
import 'package:beza_platform/core/config/app_config.dart';
import 'package:beza_platform/core/config/environment.dart';

void main() {
  group('AppConfig', () {
    test('baseUrl returns Environment.apiUrl', () {
      expect(AppConfig.baseUrl, Environment.apiUrl);
    });

    test('connectTimeout is 10 seconds', () {
      expect(AppConfig.connectTimeout, const Duration(seconds: 10));
    });

    test('receiveTimeout is 10 seconds', () {
      expect(AppConfig.receiveTimeout, const Duration(seconds: 10));
    });

    test('appName is بزة', () {
      expect(AppConfig.appName, 'بزة');
    });

    test('version is 1.0.0', () {
      expect(AppConfig.version, '1.0.0');
    });

    test('debugLogging returns debug mode', () {
      expect(AppConfig.debugLogging, Environment.debugMode);
    });
  });

  group('Environment', () {
    test('default environment is dev', () {
      expect(Environment.current, Environment.dev);
    });

    test('isDev returns true by default', () {
      expect(Environment.isDev, true);
    });

    test('isProduction returns false by default', () {
      expect(Environment.isProduction, false);
    });

    test('debugMode returns true by default (dev)', () {
      expect(Environment.debugMode, true);
    });

    test('apiPrefix is /api/v1', () {
      expect(Environment.apiPrefix, '/api/v1');
    });

    test('baseUrl returns dev URL by default', () {
      expect(Environment.baseUrl, 'http://10.0.2.2:8000');
    });

    test('apiUrl concatenates baseUrl and apiPrefix', () {
      expect(Environment.apiUrl, '${Environment.baseUrl}${Environment.apiPrefix}');
    });
  });
}
