import 'environment.dart';

class AppConfig {
  AppConfig._();

  static String get baseUrl => Environment.apiUrl;

  static Duration get connectTimeout => const Duration(seconds: 10);
  static Duration get receiveTimeout => const Duration(seconds: 10);

  static String get appName => 'بزة';
  static String get version => '1.0.0';
  static bool get debugLogging => Environment.debugMode;
}
