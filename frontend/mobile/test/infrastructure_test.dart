import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:beza_mobile/config/app_config.dart';
import 'package:beza_mobile/services/crash_reporter.dart';

void main() {
  group('Build Flavors — API Environment Switching', () {
    test('development flavor uses localhost', () {
      final config = AppConfig.development();
      expect(config.flavor, AppFlavor.development);
      expect(config.apiBaseUrl, 'http://localhost:8000/api');
      expect(config.enableDebugLogging, isTrue);
      expect(config.isDevelopment, isTrue);
    });

    test('staging flavor uses staging API', () {
      final config = AppConfig.staging();
      expect(config.flavor, AppFlavor.staging);
      expect(config.apiBaseUrl, 'https://staging.api.beza.sy/api');
      expect(config.enableDebugLogging, isFalse);
      expect(config.isStaging, isTrue);
    });

    test('production flavor uses production API', () {
      final config = AppConfig.production();
      expect(config.flavor, AppFlavor.production);
      expect(config.apiBaseUrl, 'https://api.beza.sy/api');
      expect(config.enableDebugLogging, isFalse);
      expect(config.isProduction, isTrue);
    });
  });

  group('Crash Reporting — Exception Capture', () {
    test('captures and stores exception report', () async {
      final reporter = CrashReporter();
      await reporter.initialize();

      expect(reporter.isInitialized, isTrue);
      expect(reporter.reportCount, 0);

      reporter.captureException(
        StateError('محاكاة خطأ'),
        StackTrace.current,
      );

      expect(reporter.reportCount, 1);
      expect(reporter.reports.first.message, contains('محاكاة خطأ'));
    });

    test('report serialization contains required fields', () async {
      final reporter = CrashReporter();
      await reporter.initialize();

      reporter.captureException(
        ArgumentError('قيمة غير صالحة'),
        StackTrace.current,
      );

      final report = reporter.reports.first;
      expect(report.id, isNotEmpty);
      expect(report.timestamp, isNotNull);

      final json = report.toJson();
      expect(json['id'], isNotEmpty);
      expect(json['message'], contains('قيمة غير صالحة'));
      expect(json['stack_trace'], isNotEmpty);
      expect(json['platform'], isNotEmpty);
      expect(json['timestamp'], isNotEmpty);
    });
  });

  group('Production Build — No Debug Data', () {
    test('production config disables debug logging', () {
      final config = AppConfig.production();
      expect(config.enableDebugLogging, isFalse);
      expect(config.isProduction, isTrue);
    });

    test('production config has correct app name', () {
      final config = AppConfig.production();
      expect(config.appName, 'بيزا');
    });
  });

  group('Build Failure Simulation', () {
    test('development allows debug banner, production does not', () {
      final devConfig = AppConfig.development();
      final prodConfig = AppConfig.production();

      expect(devConfig.enableDebugLogging, isTrue);
      expect(prodConfig.enableDebugLogging, isFalse);
    });
  });
}
