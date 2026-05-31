import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import '../core/money.dart';
import '../services/auth_service.dart';
import '../services/secure_storage_service.dart';
import 'login_screen.dart';
import 'transfer_screen.dart';

class HomeScreen extends StatefulWidget {
  final AuthService authService;

  const HomeScreen({super.key, required this.authService});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final SecureStorageService _storage = SecureStorageService();
  Money? _balance;
  bool _isLoading = false;
  String? _errorMessage;
  String _userName = '';

  @override
  void initState() {
    super.initState();
    _loadUserData();
    _fetchBalance();
  }

  Future<void> _loadUserData() async {
    final data = await _storage.getUserData();
    setState(() {
      _userName = data['name'] ?? 'مستخدم';
    });
  }

  Future<void> _fetchBalance() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final token = await _storage.getToken();
    if (token == null) {
      _redirectToLogin();
      return;
    }

    final client = HttpClient();
    try {
      final request = await client.getUrl(
          Uri.parse('${widget.authService.baseUrl}/v1/wallet/balance'));
      request.headers.set('Authorization', 'Bearer $token');
      request.headers.set('Accept', 'application/json');
      final response = await request.close();
      final body = await response.transform(utf8.decoder).join();
      final jsonResponse = json.decode(body) as Map<String, dynamic>;

      if (jsonResponse['success'] == true && jsonResponse['data'] != null) {
        final data = jsonResponse['data'] as Map<String, dynamic>;
        final balanceFils = data['balance_fils'] as int;
        setState(() {
          _balance = Money.fromFils(balanceFils);
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = 'فشل جلب الرصيد';
          _isLoading = false;
        });
      }
    } on SocketException {
      setState(() {
        _errorMessage = 'تعذر الاتصال بالخادم. يرجى المحاولة لاحقاً.';
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = 'حدث خطأ غير متوقع.';
        _isLoading = false;
      });
    } finally {
      client.close();
    }
  }

  Future<void> _navigateToTransfer() async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => TransferScreen(
          baseUrl: widget.authService.baseUrl,
        ),
      ),
    );
    _fetchBalance();
  }

  Future<void> _logout() async {
    await widget.authService.logout();
    if (mounted) {
      _redirectToLogin();
    }
  }

  void _redirectToLogin() {
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(
        builder: (_) => LoginScreen(authService: widget.authService),
      ),
      (route) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('بيزا'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _logout,
            tooltip: 'تسجيل خروج',
          ),
        ],
      ),
      body: Directionality(
        textDirection: TextDirection.rtl,
        child: RefreshIndicator(
          onRefresh: _fetchBalance,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    children: [
                      Text(
                        'مرحباً، $_userName',
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 16),
                      if (_isLoading)
                        const CircularProgressIndicator()
                      else if (_errorMessage != null)
                        Column(
                          children: [
                            const Icon(Icons.error_outline,
                                size: 48, color: Colors.red),
                            const SizedBox(height: 8),
                            Text(_errorMessage!,
                                textAlign: TextAlign.center),
                            const SizedBox(height: 8),
                            ElevatedButton(
                              onPressed: _fetchBalance,
                              child: const Text('إعادة المحاولة'),
                            ),
                          ],
                        )
                      else
                        Column(
                          children: [
                            const Text('رصيدك الحالي',
                                style: TextStyle(fontSize: 14, color: Colors.grey)),
                            const SizedBox(height: 8),
                            Text(
                              _balance?.format() ?? '0.000 ل.س',
                              style: Theme.of(context)
                                  .textTheme
                                  .headlineLarge
                                  ?.copyWith(
                                    fontWeight: FontWeight.bold,
                                    color: Theme.of(context).colorScheme.primary,
                                  ),
                            ),
                          ],
                        ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton.icon(
                  onPressed: _navigateToTransfer,
                  icon: const Icon(Icons.send),
                  label: const Text('تحويل', style: TextStyle(fontSize: 16)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

