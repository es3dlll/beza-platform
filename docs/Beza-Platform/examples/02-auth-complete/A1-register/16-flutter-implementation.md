# 16 - تطبيق Flutter (Flutter Implementation) — تسجيل (Register)

## هيكل الملفات

```
lib/features/auth/
├── data/
│   ├── models/
│   │   ├── register_request_model.dart
│   │   └── register_response_model.dart
│   └── repositories/
│       └── auth_repository.dart
├── domain/
│   ├── entities/
│   │   └── user_entity.dart
│   └── repositories/
│       └── i_auth_repository.dart
└── presentation/
    ├── bloc/
    │   ├── auth_bloc.dart
    │   ├── auth_event.dart
    │   └── auth_state.dart
    ├── screens/
    │   └── register_screen.dart
    └── widgets/
        └── register_form.dart
```

## طبقة المجال (Domain Layer)

```dart
// domain/entities/user_entity.dart
class UserEntity {
  final int id;
  final String uuid;
  final String name;
  final String phone;
  final String status;
  final String kycStatus;

  UserEntity({
    required this.id, required this.uuid, required this.name,
    required this.phone, required this.status, required this.kycStatus,
  });
}
```

```dart
// domain/repositories/i_auth_repository.dart
abstract class IAuthRepository {
  Future<AuthResult> register({
    required String name,
    required String phone,
    required String password,
    required String passwordConfirmation,
    required String pinCode,
    required String pinCodeConfirmation,
    String? deviceId,
  });
}

class AuthResult {
  final UserEntity user;
  final List<Map<String, dynamic>> wallets;
  final String token;
  final int expiresIn;

  AuthResult({required this.user, required this.wallets, required this.token, this.expiresIn = 3600});
}
```

## طبقة البيانات (Data Layer)

```dart
// data/models/register_request_model.dart
class RegisterRequestModel {
  final String name;
  final String phone;
  final String password;
  final String passwordConfirmation;
  final String pinCode;
  final String pinCodeConfirmation;
  final String? deviceId;

  Map<String, dynamic> toJson() => {
    'name': name,
    'phone': phone,
    'password': password,
    'password_confirmation': passwordConfirmation,
    'pin_code': pinCode,
    'pin_code_confirmation': pinCodeConfirmation,
    'device_id': deviceId,
  };
}
```

```dart
// data/repositories/auth_repository.dart
class AuthRepository implements IAuthRepository {
  final http.Client client;
  final String baseUrl;

  AuthRepository({required this.baseUrl, required this.client});

  @override
  Future<AuthResult> register({...}) async {
    final request = RegisterRequestModel(...);
    final response = await client.post(
      Uri.parse('$baseUrl/api/v1/auth/register'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
      body: jsonEncode(request.toJson()),
    );
    final body = jsonDecode(response.body);

    if (response.statusCode == 201) {
      return AuthResult(
        user: UserEntity(
          id: body['data']['user']['id'],
          uuid: body['data']['user']['uuid'],
          name: body['data']['user']['name'],
          phone: body['data']['user']['phone'],
          status: body['data']['user']['status'],
          kycStatus: body['data']['user']['kyc_status'],
        ),
        wallets: body['data']['wallets'],
        token: body['data']['token'],
        expiresIn: body['data']['expires_in'] ?? 3600,
      );
    }
    throw Exception(body['message']);
  }
}
```

## خدمات المصادقة (TokenService + AuthInterceptor)

```dart
// services/token_service.dart
class TokenService {
  static const _tokenKey = 'access_token';
  static const _expiresAtKey = 'expires_at';
  static const _refreshTokenKey = 'refresh_token';

  final FlutterSecureStorage _storage;

  TokenService(this._storage);

  Future<void> saveTokens({
    required String accessToken,
    required int expiresIn,
    String? refreshToken,
  }) async {
    final expiresAt = DateTime.now().toUtc().add(Duration(seconds: expiresIn));
    await _storage.write(key: _tokenKey, value: accessToken);
    await _storage.write(key: _expiresAtKey, value: expiresAt.toIso8601String());
    if (refreshToken != null) {
      await _storage.write(key: _refreshTokenKey, value: refreshToken);
    }
  }

  Future<String?> getValidToken() async {
    final token = await _storage.read(key: _tokenKey);
    if (token == null) return null;

    final expiresAtStr = await _storage.read(key: _expiresAtKey);
    if (expiresAtStr != null) {
      final expiresAt = DateTime.parse(expiresAtStr);
      if (DateTime.now().toUtc().isAfter(expiresAt)) {
        return await _refreshToken();
      }
    }
    return token;
  }

  Future<bool> isExpired() async {
    final expiresAtStr = await _storage.read(key: _expiresAtKey);
    if (expiresAtStr == null) return true;
    return DateTime.now().toUtc().isAfter(DateTime.parse(expiresAtStr));
  }

  Future<String?> _refreshToken() async {
    final refreshToken = await _storage.read(key: _refreshTokenKey);
    if (refreshToken == null) return null;
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/v1/auth/refresh'),
        headers: {'Authorization': 'Bearer $refreshToken'},
      );
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        await saveTokens(
          accessToken: body['data']['token'],
          expiresIn: body['data']['expires_in'] ?? 3600,
        );
        return body['data']['token'];
      }
    } catch (_) {}
    await clearToken();
    return null;
  }

  Future<void> clearToken() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _expiresAtKey);
    await _storage.delete(key: _refreshTokenKey);
  }
}
```

```dart
// services/auth_interceptor.dart
class AuthInterceptor extends Interceptor {
  final TokenService _tokenService;
  final Dio _dio;

  AuthInterceptor(this._tokenService, this._dio);

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await _tokenService.getValidToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    if (err.response?.statusCode == 401) {
      final refreshed = await _tokenService.getValidToken();
      if (refreshed != null) {
        err.requestOptions.headers['Authorization'] = 'Bearer $refreshed';
        final response = await _dio.fetch(err.requestOptions);
        handler.resolve(response);
        return;
      }
      await _tokenService.clearToken();
    }
    handler.next(err);
  }
}
```

## طبقة العرض (Presentation Layer) (BLoC)

```dart
// events
class RegisterSubmitted extends AuthEvent {
  final String name, phone, password, passwordConfirmation, pinCode, pinCodeConfirmation;
  RegisterSubmitted({...});
}

// bloc
class AuthBloc extends Bloc<AuthEvent, AuthState> {
  Future<void> _onRegister(RegisterSubmitted event, Emitter emit) async {
    emit(AuthLoading());
    try {
      final result = await repository.register(...);
      await TokenService(FlutterSecureStorage()).saveTokens(
        accessToken: result.token,
        expiresIn: result.expiresIn,
      );
      emit(AuthSuccess(result));
    } catch (e) {
      emit(AuthFailure(e.toString()));
    }
  }
}
```

## UI

```dart
class RegisterForm extends StatefulWidget {
  @override
  Widget build(BuildContext context) {
    return Form(
      child: ListView(children: [
        TextFormField(decoration: InputDecoration(labelText: 'الاسم'), validator: (v) => v?.isEmpty == true ? 'مطلوب' : null),
        TextFormField(decoration: InputDecoration(labelText: 'رقم الهاتف', prefixText: '09'), keyboardType: TextInputType.phone, maxLength: 10),
        TextFormField(decoration: InputDecoration(labelText: 'كلمة المرور'), obscureText: true, validator: (v) => v!.length < 8 ? '8 أحرف على الأقل' : null),
        TextFormField(decoration: InputDecoration(labelText: 'تأكيد كلمة المرور'), obscureText: true),
        TextFormField(decoration: InputDecoration(labelText: 'PIN'), obscureText: true, maxLength: 4, keyboardType: TextInputType.number),
        TextFormField(decoration: InputDecoration(labelText: 'تأكيد PIN'), obscureText: true, maxLength: 4),
        SizedBox(height: 24),
        ElevatedButton(onPressed: _submit, child: Text('تسجيل')),
      ]),
    );
  }
}
```
