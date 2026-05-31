import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'config/app_config.dart';
import 'core/providers/transfer_provider.dart';
import 'services/auth_service.dart';
import 'services/crash_reporter.dart';
import 'widgets/route_guard.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  final config = AppConfig.development();
  crashReporter.initialize();

  final authService = AuthService(baseUrl: config.apiBaseUrl);

  runApp(
    ProviderScope(
      overrides: [
        appConfigProvider.overrideWithValue(config),
      ],
      child: BezaApp(
        authService: authService,
        config: config,
      ),
    ),
  );
}

class BezaApp extends StatelessWidget {
  final AuthService authService;
  final AppConfig config;

  const BezaApp({
    super.key,
    required this.authService,
    required this.config,
  });

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: config.appName,
      debugShowCheckedModeBanner: config.isDevelopment,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF1A6B4E)),
        useMaterial3: true,
      ),
      home: RouteGuard(authService: authService),
    );
  }
}
