# 16 - تطبيق Flutter (Flutter Implementation) - A5: المصادقة الثنائية (2FA - TOTP)

## طبقة المجال (Domain Layer)

```dart
// domain/repositories/i_two_factor_repository.dart
abstract class ITwoFactorRepository {
  Future<TwoFactorSetupData> enable2fa();
  Future<void> verify2fa(String code);
  Future<void> disable2fa(String password, {String? code});
}

class TwoFactorSetupData {
  final String qrCode;
  final String secret;
  TwoFactorSetupData({required this.qrCode, required this.secret});
}
```

## طبقة البيانات (Data Layer)

```dart
// data/repositories/two_factor_repository.dart
class TwoFactorRepository implements ITwoFactorRepository {
  final http.Client client;
  final String baseUrl;

  Future<String> _getToken() async {
    return await TokenService(FlutterSecureStorage()).getValidToken() ?? '';
  }

  Map<String, String> _headers(String token) => {
    'Accept': 'application/json',
    'Authorization': 'Bearer $token',
  };

  @override
  Future<TwoFactorSetupData> enable2fa() async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('$baseUrl/api/v1/auth/2fa/enable'),
      headers: _headers(token),
    );
    final body = jsonDecode(response.body);
    return TwoFactorSetupData(
      qrCode: body['data']['qr_code'],
      secret: body['data']['secret'],
    );
  }

  @override
  Future<void> verify2fa(String code) async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('$baseUrl/api/v1/auth/2fa/verify'),
      headers: {..._headers(token), 'Content-Type': 'application/json'},
      body: jsonEncode({'code': code}),
    );
    if (response.statusCode != 200) {
      throw Exception(jsonDecode(response.body)['message']);
    }
  }
}
```

## UI

```dart
class TwoFactorSetupScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => TwoFactorBloc(repository: TwoFactorRepository(...)),
      child: BlocConsumer<TwoFactorBloc, TwoFactorState>(
        listener: (context, state) {
          if (state is TwoFactorVerified) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text('تم تفعيل المصادقة الثنائية')),
            );
            Navigator.pop(context);
          }
        },
        builder: (context, state) {
          if (state is TwoFactorLoading) return Center(child: CircularProgressIndicator());

          if (state is TwoFactorSetup) {
            return Scaffold(
              appBar: AppBar(title: Text('تفعيل 2FA')),
              body: Padding(
                padding: EdgeInsets.all(24),
                child: Column(children: [
                  Text('امسح رمز QR ضوئياً باستخدام Google Authenticator'),
                  SizedBox(height: 24),
                  Image.memory(base64Decode(state.qrCode.split(',').last), width: 200, height: 200),
                  SizedBox(height: 24),
                  Text('أو أدخل المفتاح يدوياً:', style: TextStyle(fontWeight: FontWeight.bold)),
                  Text(state.secret, style: TextStyle(fontSize: 18, letterSpacing: 2)),
                  SizedBox(height: 32),
                  TextField(
                    decoration: InputDecoration(labelText: 'رمز التحقق', hintText: '6 أرقام'),
                    keyboardType: TextInputType.number,
                    maxLength: 6,
                    onSubmitted: (code) => context.read<TwoFactorBloc>().add(VerifyTwoFactor(code)),
                  ),
                  SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () {
                      final code = // get from controller
                      context.read<TwoFactorBloc>().add(VerifyTwoFactor(code));
                    },
                    child: Text('تأكيد'),
                  ),
                ]),
              ),
            );
          }

          return Scaffold(
            appBar: AppBar(title: Text('2FA')),
            body: Center(
              child: ElevatedButton(
                onPressed: () => context.read<TwoFactorBloc>().add(EnableTwoFactor()),
                child: Text('تفعيل المصادقة الثنائية'),
              ),
            ),
          );
        },
      ),
    );
  }
}
```
