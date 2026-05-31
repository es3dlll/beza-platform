enum AppFlavor { development, staging, production }

class AppConfig {
  final AppFlavor flavor;
  final String apiBaseUrl;
  final bool enableDebugLogging;
  final bool enableCrashReporting;
  final bool enablePerformanceMonitoring;
  final String appName;

  const AppConfig({
    required this.flavor,
    required this.apiBaseUrl,
    this.enableDebugLogging = false,
    this.enableCrashReporting = false,
    this.enablePerformanceMonitoring = false,
    this.appName = 'بيزا',
  });

  bool get isProduction => flavor == AppFlavor.production;
  bool get isDevelopment => flavor == AppFlavor.development;
  bool get isStaging => flavor == AppFlavor.staging;

  static AppConfig development() => const AppConfig(
        flavor: AppFlavor.development,
        apiBaseUrl: 'http://localhost:8000/api',
        enableDebugLogging: true,
        enableCrashReporting: false,
        enablePerformanceMonitoring: false,
        appName: 'بيزا-تطوير',
      );

  static AppConfig staging() => const AppConfig(
        flavor: AppFlavor.staging,
        apiBaseUrl: 'https://staging.api.beza.sy/api',
        enableDebugLogging: false,
        enableCrashReporting: true,
        enablePerformanceMonitoring: true,
        appName: 'بيزا-تجريبي',
      );

  static AppConfig production() => const AppConfig(
        flavor: AppFlavor.production,
        apiBaseUrl: 'https://api.beza.sy/api',
        enableDebugLogging: false,
        enableCrashReporting: true,
        enablePerformanceMonitoring: true,
        appName: 'بيزا',
      );
}
