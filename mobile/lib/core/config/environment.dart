class Environment {
  Environment._();

  static const String dev = 'dev';
  static const String staging = 'staging';
  static const String production = 'production';

  static const String current =
      String.fromEnvironment('APP_ENV', defaultValue: dev);

  static bool get isProduction => current == production;
  static bool get isDev => current == dev;

  static String get baseUrl {
    const override = String.fromEnvironment('API_BASE_URL');
    if (override.isNotEmpty) return override;
    return isProduction ? 'https://api.bezafinance.com' : 'http://10.0.2.2:8000';
  }

  static String get apiPrefix => '/api/v1';
  static String get apiUrl => '$baseUrl$apiPrefix';

  /// Usage:
  ///   flutter run --dart-define=API_BASE_URL=http://localhost:8000      (iOS simulator)
  ///   flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000      (Android emulator)
  ///   flutter run --dart-define=API_BASE_URL=http://192.168.1.5:8000   (real device)

  static bool get debugMode => !isProduction;
  static bool get logApiCalls => !isProduction;
}
