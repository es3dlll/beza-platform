import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../screens/login_screen.dart';
import '../screens/home_screen.dart';

class RouteGuard extends StatefulWidget {
  final AuthService authService;
  final Widget Function()? onAuthenticated;

  const RouteGuard({
    super.key,
    required this.authService,
    this.onAuthenticated,
  });

  @override
  State<RouteGuard> createState() => _RouteGuardState();
}

class _RouteGuardState extends State<RouteGuard> {
  bool _isChecking = true;
  bool _isAuthenticated = false;

  @override
  void initState() {
    super.initState();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    await widget.authService.checkAuthStatus();
    if (mounted) {
      setState(() {
        _isAuthenticated = widget.authService.isAuthenticated;
        _isChecking = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isChecking) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (!_isAuthenticated) {
      return LoginScreen(authService: widget.authService);
    }

    return widget.onAuthenticated?.call() ??
        HomeScreen(authService: widget.authService);
  }
}
