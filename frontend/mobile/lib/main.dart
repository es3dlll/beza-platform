import 'package:flutter/material.dart';
import 'services/auth_service.dart';
import 'widgets/route_guard.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  final authService = AuthService(baseUrl: 'http://localhost:8000/api');

  runApp(BezaApp(authService: authService));
}

class BezaApp extends StatelessWidget {
  final AuthService authService;

  const BezaApp({super.key, required this.authService});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'بيزا',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF1A6B4E)),
        useMaterial3: true,
      ),
      home: RouteGuard(authService: authService),
    );
  }
}
