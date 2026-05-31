# 16 - تطبيق Flutter (Flutter Implementation) — تسجيل الخروج (Logout)

## AuthRepository

```dart
// data/repositories/auth_repository.dart
class AuthRepository implements IAuthRepository {
  final TokenService _tokenService;

  AuthRepository(this._tokenService);

  @override
  Future<void> logout() async {
    final token = await _tokenService.getValidToken();

    final response = await client.post(
      Uri.parse('$baseUrl/api/v1/auth/logout'),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    // مسح التوكن من التخزين الآمن بغض النظر عن نتيجة API
    await _tokenService.clearToken();

    if (response.statusCode != 200) {
      throw Exception('فشل تسجيل الخروج');
    }
  }
}
```

## Settings Screen (حيث يكون زر الخروج)

```dart
class SettingsScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('الإعدادات')),
      body: ListView(children: [
        ListTile(
          leading: Icon(Icons.security),
          title: Text('المصادقة الثنائية'),
          onTap: () => Navigator.pushNamed(context, '/2fa-setup'),
        ),
        ListTile(
          leading: Icon(Icons.logout, color: Colors.red),
          title: Text('تسجيل الخروج'),
          onTap: () => _showLogoutDialog(context),
        ),
      ]),
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('تسجيل الخروج'),
        content: Text('هل أنت متأكد؟'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: Text('إلغاء')),
          TextButton(
            onPressed: () async {
              await context.read<AuthBloc>().add(LogoutSubmitted());
              Navigator.pushNamedAndRemoveUntil(context, '/login', (route) => false);
            },
            child: Text('تسجيل خروج', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }
}
```

## AuthBloc

```dart
// presentation/bloc/auth_event.dart
class LogoutSubmitted extends AuthEvent {}

// presentation/bloc/auth_bloc.dart
Future<void> _onLogout(LogoutSubmitted event, Emitter emit) async {
  emit(AuthLoading());
  try {
    await repository.logout();
    emit(AuthLoggedOut());
  } catch (e) {
    emit(AuthError(e.toString()));
  }
}
```
